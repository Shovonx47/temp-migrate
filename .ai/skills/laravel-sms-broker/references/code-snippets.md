# Code Snippets

Reference implementations for the core components of the Frolax SMS broker. These match the migration column names exactly. Adapt to the app's conventions when implementing.

## Table of contents

- [SmsManager + fluent builder](#smsmanager--fluent-builder)
- [BalanceService](#balanceservice)
- [SendSmsJob](#sendsmsjob)
- [Redis RateLimiter](#redis-ratelimiter)
- [Webhook controller](#webhook-controller)
- [Sample driver: SSL Wireless](#sample-driver-ssl-wireless)
- [Customer API: send endpoint](#customer-api-send-endpoint)

---

## SmsManager + fluent builder

```php
<?php

namespace App\Services\Sms;

use App\Enums\MessageStatus;
use App\Enums\TransactionType;
use App\Jobs\SendSmsJob;
use App\Models\Gateway;
use App\Models\Message;
use App\Models\Sender;
use App\Models\Template;
use App\Models\User;
use App\Services\Sms\DTOs\SendRequest;
use App\Services\Sms\Exceptions\CapabilityMissingException;
use App\Services\Sms\Exceptions\GatewayUnavailableException;
use App\Services\Sms\Exceptions\InsufficientBalanceException;
use App\Services\Sms\Exceptions\SenderNotAllowedException;
use Illuminate\Support\Facades\DB;

class SmsManager
{
    public function for(User $user): PendingSms
    {
        return new PendingSms($this, $user);
    }

    public function dispatch(PendingSms $p): Message
    {
        // 1. Sender allocation check (sender_user pivot)
        if (! $p->user->senders()->whereKey($p->sender->id)->exists()) {
            throw new SenderNotAllowedException("User {$p->user->id} has no access to sender {$p->sender->id}.");
        }

        // 2. Gateway must be enabled
        $gateway = $p->sender->gateway;
        if (! $gateway || ! $gateway->is_enabled) {
            throw new GatewayUnavailableException("Gateway for sender {$p->sender->sender_id} is not enabled.");
        }

        // 3. Resolve body (template interpolation if any)
        $body = $this->resolveBody($p);

        // 4. Capability check
        $driver = new (DriverRegistry::resolve($gateway->driver))($gateway);
        $caps = $driver->capabilities();
        if (! mb_check_encoding($body, 'ASCII') && ! in_array('unicode', $caps)) {
            throw new CapabilityMissingException("Gateway '{$gateway->name}' does not support unicode.");
        }

        // 5. Estimate cost, debit balance, create message — all in a single DB transaction
        $cost = app(BalanceService::class)->estimateCost($gateway, $body);

        return DB::transaction(function () use ($p, $gateway, $body, $cost) {
            $message = Message::create([
                'user_id' => $p->user->id,
                'campaign_id' => $p->campaignId,
                'sender_id' => $p->sender->id,
                'gateway_id' => $gateway->id,        // snapshot, immutable
                'to' => $p->to,
                'body' => $body,
                'status' => MessageStatus::Pending,
                'cost' => $cost,
            ]);

            app(BalanceService::class)->debit(
                user: $p->user->fresh(),
                amount: $cost,
                type: TransactionType::SmsDeduction,
                linkable: $message,
                description: "SMS to {$p->to}",
            );

            SendSmsJob::dispatch($message->id)->onQueue('sms');

            return $message;
        });
    }

    protected function resolveBody(PendingSms $p): string
    {
        if ($p->body) return $p->body;
        if ($p->template) {
            return preg_replace_callback(
                '/\{(\w+)\}/',
                fn($m) => $p->vars[$m[1]] ?? $m[0],
                $p->template->body,
            );
        }
        throw new \InvalidArgumentException('SMS has neither body nor template.');
    }
}
```

`PendingSms` is a small builder with `from(Sender)`, `to(string)`, `body(string)`, `template(Template, array)`, `inCampaign(int)`, and `send()` that calls `$manager->dispatch($this)`.

---

## BalanceService

```php
<?php

namespace App\Services\Sms;

use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Models\Gateway;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Sms\Exceptions\InsufficientBalanceException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    public function debit(User $user, float $amount, TransactionType $type, Model $linkable, ?string $description = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $type, $linkable, $description) {
            $user = User::lockForUpdate()->findOrFail($user->id);

            if ($user->balance < $amount) {
                throw new InsufficientBalanceException("User {$user->id} balance {$user->balance} < required {$amount}.");
            }

            $before = $user->balance;
            $after = $before - $amount;
            $user->update(['balance' => $after]);

            return Transaction::create([
                'user_id' => $user->id,
                'transactionable_type' => $linkable->getMorphClass(),
                'transactionable_id' => $linkable->getKey(),
                'type' => $type,
                'amount' => $amount,
                'direction' => TransactionDirection::Debit,
                'balance_before' => $before,
                'balance_after' => $after,
                'description' => $description,
            ]);
        });
    }

    public function credit(User $user, float $amount, TransactionType $type, Model $linkable, ?string $description = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $type, $linkable, $description) {
            $user = User::lockForUpdate()->findOrFail($user->id);

            $before = $user->balance;
            $after = $before + $amount;
            $user->update(['balance' => $after]);

            return Transaction::create([
                'user_id' => $user->id,
                'transactionable_type' => $linkable->getMorphClass(),
                'transactionable_id' => $linkable->getKey(),
                'type' => $type,
                'amount' => $amount,
                'direction' => TransactionDirection::Credit,
                'balance_before' => $before,
                'balance_after' => $after,
                'description' => $description,
            ]);
        });
    }

    public function estimateCost(Gateway $gateway, string $body): float
    {
        $perSegment = (float) ($gateway->config['cost_per_segment'] ?? 0);
        $segmentSize = mb_check_encoding($body, 'ASCII') ? 160 : 70;
        $segments = max(1, (int) ceil(mb_strlen($body) / $segmentSize));
        return round($perSegment * $segments, 4);
    }
}
```

---

## SendSmsJob

```php
<?php

namespace App\Jobs;

use App\Enums\MessageStatus;
use App\Enums\TransactionType;
use App\Events\MessageFailed;
use App\Events\MessageSent;
use App\Models\Message;
use App\Services\Sms\BalanceService;
use App\Services\Sms\DriverRegistry;
use App\Services\Sms\DTOs\SendRequest;
use App\Services\Sms\Exceptions\GatewayRateLimitedException;
use App\Services\Sms\Exceptions\GatewayUnavailableException;
use App\Services\Sms\RateLimiter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(public int $messageId) {}

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(RateLimiter $limiter, BalanceService $balance): void
    {
        $message = Message::with('gateway', 'sender', 'user')->findOrFail($this->messageId);

        // Idempotency — already terminal?
        if (in_array($message->status, [MessageStatus::Sent, MessageStatus::Delivered, MessageStatus::Failed])) {
            return;
        }

        $gateway = $message->gateway;

        if (! $limiter->allow($gateway)) {
            $this->release(30); // doesn't consume a retry
            return;
        }

        $driver = new (DriverRegistry::resolve($gateway->driver))($gateway);
        $request = new SendRequest(
            user: $message->user,
            sender: $message->sender,
            to: $message->to,
            body: $message->body,
        );

        try {
            $result = $driver->send($request);

            if ($result->ok) {
                $message->update([
                    'status' => MessageStatus::Sent,
                    'gateway_message_id' => $result->providerId,
                    'sent_at' => now(),
                ]);
                $limiter->record($gateway);
                event(new MessageSent($message));
            } else {
                $this->fail($message, $balance, $result->error ?? 'Driver returned not-ok.');
            }
        } catch (GatewayRateLimitedException $e) {
            $this->release(60);
        } catch (GatewayUnavailableException $e) {
            $message->update(['failed_reason' => $e->getMessage()]);
            throw $e; // let Laravel handle retry
        } catch (\Throwable $e) {
            $this->fail($message, $balance, $e->getMessage());
        }
    }

    protected function fail(Message $msg, BalanceService $balance, string $reason): void
    {
        $msg->update([
            'status' => MessageStatus::Failed,
            'failed_reason' => $reason,
            'failed_at' => now(),
        ]);

        // Refund the customer
        $balance->credit(
            user: $msg->user,
            amount: (float) $msg->cost,
            type: TransactionType::Refund,
            linkable: $msg,
            description: "Refund for failed message #{$msg->id}",
        );

        event(new MessageFailed($msg));
    }
}
```

---

## Redis RateLimiter

```php
<?php

namespace App\Services\Sms;

use App\Models\Gateway;
use Illuminate\Support\Facades\Redis;

class RateLimiter
{
    public function allow(Gateway $gateway): bool
    {
        $minuteKey = $this->minuteKey($gateway);
        $dayKey = $this->dayKey($gateway);
        $monthKey = $this->monthKey($gateway);

        $minute = (int) Redis::get($minuteKey);
        $day = (int) Redis::get($dayKey);
        $month = (int) Redis::get($monthKey);

        $minuteLimit = $gateway->config['rate_limit_per_minute'] ?? null;
        if ($minuteLimit && $minute >= $minuteLimit) return false;
        if ($gateway->daily_limit && $day >= $gateway->daily_limit) return false;
        if ($gateway->monthly_limit && $month >= $gateway->monthly_limit) return false;

        return true;
    }

    public function record(Gateway $gateway): void
    {
        Redis::pipeline(function ($pipe) use ($gateway) {
            $pipe->incr($this->minuteKey($gateway));
            $pipe->expire($this->minuteKey($gateway), 120);
            $pipe->incr($this->dayKey($gateway));
            $pipe->expire($this->dayKey($gateway), 86_400 * 2);
            $pipe->incr($this->monthKey($gateway));
            $pipe->expire($this->monthKey($gateway), 86_400 * 35);
        });
    }

    protected function minuteKey(Gateway $g): string
    {
        return "sms:rl:{$g->id}:minute:" . now()->format('YmdHi');
    }

    protected function dayKey(Gateway $g): string
    {
        return "sms:rl:{$g->id}:day:" . now()->format('Ymd');
    }

    protected function monthKey(Gateway $g): string
    {
        return "sms:rl:{$g->id}:month:" . now()->format('Ym');
    }
}
```

---

## Webhook controller

```php
<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\MessageStatus;
use App\Models\DeliveryReport;
use App\Models\Gateway;
use App\Models\Message;
use App\Services\Sms\DriverRegistry;
use Illuminate\Http\Request;

class SmsDeliveryWebhookController
{
    public function handle(Request $request, Gateway $gateway)
    {
        if (! $gateway->is_enabled) abort(404);

        $driver = new (DriverRegistry::resolve($gateway->driver))($gateway);

        if (! $driver->verifySignature($request)) {
            abort(403, 'Invalid signature.');
        }

        $update = $driver->parseDeliveryReport($request->all());

        if (! $update) {
            // Not a recognized payload — log raw and return 200 so provider stops retrying.
            DeliveryReport::create([
                'gateway_id' => $gateway->id,
                'status' => 'unknown',
                'raw_payload' => $request->all(),
                'received_at' => now(),
            ]);
            return response('OK');
        }

        $message = Message::where('gateway_message_id', $update->providerId)
            ->where('gateway_id', $gateway->id)
            ->first();

        DeliveryReport::create([
            'gateway_id' => $gateway->id,
            'message_id' => $message?->id,
            'gateway_message_id' => $update->providerId,
            'status' => $update->status,
            'raw_payload' => $request->all(),
            'received_at' => now(),
        ]);

        if ($message && in_array($update->status, ['delivered', 'failed', 'rejected'])) {
            $message->update([
                'status' => MessageStatus::from($update->status === 'rejected' ? 'failed' : $update->status),
                $update->status === 'delivered' ? 'delivered_at' : 'failed_at' => now(),
            ]);

            // Note: don't refund on `rejected` from DLR — at this point we already
            // recorded the send as successful with the upstream. Rejection at the
            // carrier level is the customer's risk. Refund policy is a business
            // decision — confirm with the user before adding refund-on-DLR-rejection.
        }

        return response('OK');
    }
}
```

Route:
```php
// routes/api.php (no CSRF)
Route::post('/webhooks/sms/{gateway:name}', [SmsDeliveryWebhookController::class, 'handle'])
    ->name('webhooks.sms');
```

---

## Sample driver: SSL Wireless

```php
<?php

namespace App\Services\Sms\Drivers;

use App\Models\Gateway;
use App\Services\Sms\Contracts\SmsDriver;
use App\Services\Sms\DTOs\DeliveryUpdate;
use App\Services\Sms\DTOs\DriverResult;
use App\Services\Sms\DTOs\SendRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SslWirelessDriver implements SmsDriver
{
    public function __construct(protected Gateway $gateway) {}

    public function capabilities(): array
    {
        return ['unicode', 'long_sms', 'delivery_reports'];
    }

    public function send(SendRequest $request): DriverResult
    {
        $creds = $this->gateway->credentials;
        $config = $this->gateway->config ?? [];

        $response = Http::timeout(15)->post(
            $config['endpoint'] ?? 'https://smsplus.sslwireless.com/api/v3/send-sms/single',
            [
                'api_token' => $creds['api_token'],
                'sid' => $request->sender->sender_id,
                'msisdn' => ltrim($request->to, '+'),
                'sms' => $request->body,
                'csms_id' => ($creds['csms_id_prefix'] ?? 'MSG') . '-' . Str::ulid(),
            ]
        );

        $data = $response->json() ?? [];

        if (($data['status'] ?? null) === 'SUCCESS') {
            return DriverResult::ok(
                providerId: $data['smsinfo'][0]['reference_id'] ?? null,
                raw: $data,
            );
        }

        return DriverResult::fail(
            error: $data['error_message'] ?? 'SSL send failed',
            raw: $data,
        );
    }

    public function parseDeliveryReport(array $payload): ?DeliveryUpdate
    {
        if (! isset($payload['reference_id'], $payload['status'])) return null;

        $map = [
            'DELIVERED' => 'delivered',
            'UNDELIVERED' => 'failed',
            'REJECTED' => 'rejected',
            'PENDING' => 'pending',
        ];

        return new DeliveryUpdate(
            providerId: $payload['reference_id'],
            status: $map[$payload['status']] ?? 'unknown',
            raw: $payload,
        );
    }

    public function verifySignature(Request $request): bool
    {
        // SSL doesn't sign — rely on IP whitelist at the web server.
        return true;
    }
}
```

---

## Customer API: send endpoint

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Sender;
use App\Services\Sms\SmsManager;
use App\Services\Sms\Exceptions\InsufficientBalanceException;
use App\Services\Sms\Exceptions\SenderNotAllowedException;
use Illuminate\Http\Request;
use Propaganistas\LaravelPhone\Rules\Phone;

class SmsController extends Controller
{
    public function send(Request $request, SmsManager $sms)
    {
        $data = $request->validate([
            'sender_id' => ['required', 'integer', 'exists:senders,id'],
            'to' => ['required', (new Phone)->country('BD')],
            'body' => ['required', 'string', 'max:1600'],
        ]);

        $user = $request->user();
        $sender = Sender::findOrFail($data['sender_id']);

        try {
            $message = $sms->for($user)
                ->from($sender)
                ->to($data['to'])
                ->body($data['body'])
                ->send();

            return new MessageResource($message);
        } catch (SenderNotAllowedException $e) {
            return response()->json(['error' => 'sender_not_allowed', 'message' => $e->getMessage()], 403);
        } catch (InsufficientBalanceException $e) {
            return response()->json(['error' => 'insufficient_balance', 'message' => $e->getMessage()], 402);
        }
    }
}
```

Route:
```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1')->group(function () {
    Route::post('/sms', [SmsController::class, 'send']);
});
```

`MessageResource` returns the message with `id`, `to`, `body`, `status`, `cost`, `sent_at`, `gateway_message_id` — but NOT `gateway_id` or internal gateway details (customer shouldn't see which upstream you used).