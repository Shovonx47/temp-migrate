<?php

namespace App\Jobs;

use App\Actions\Billing\CreditBalance;
use App\Enums\MessageStatus;
use App\Models\Batch;
use App\Models\Gateway;
use App\Models\Message;
use App\Models\User;
use App\Services\Logging\SmsLogger;
use App\Services\Sms\Data\SmsRequest;
use App\Support\Facades\Sms;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sends a single pending message via an individual API request.
 *
 * One job dispatch = one gateway API call. Rate limiting is enforced via the
 * 'sms' RateLimiter keyed by gateway ID (requests_per_second on the Gateway model).
 *
 * Use this job when:
 *   - The batch body has {variable} placeholders, OR
 *   - The resolved driver does not implement SupportsBulkSms
 *
 * The job is idempotent: if the message is no longer pending when it executes
 * (e.g. already processed by a duplicate dispatch), it exits silently.
 */
class SendBatchSingleSmsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 30;

    public int $tries = 1;

    public function __construct(
        public readonly Batch $batch,
        public readonly int $messageId,
    ) {}

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [new RateLimited('sms')];
    }

    public function retryUntil(): CarbonInterface
    {
        return now()->addHour();
    }

    public function getGateway(): ?Gateway
    {
        $message = Message::with('gateway')->find($this->messageId);

        return $message?->gateway ?? $this->batch->sender?->gateway;
    }

    public function handle(): void
    {
        $batch = $this->batch->fresh(['sender.gateway']);

        if (! $batch) {
            return;
        }

        $message = Message::where('id', $this->messageId)
            ->with(['sender.gateway', 'gateway'])
            ->select(['id', 'to', 'body', 'cost', 'user_id', 'is_unicode', 'sender_id', 'gateway_id'])
            ->first();

        if (! $message) {
            return;
        }

        $gateway = $message->gateway ?? $message->sender?->gateway ?? $batch->sender?->gateway;

        if (! $gateway) {
            return;
        }

        // Atomic idempotency guard: claim this message by flipping status from
        // Pending → Sending in a single UPDATE. If another worker already claimed
        // it (or it's no longer pending), affected rows will be 0 and we exit.
        $claimed = DB::table('messages')
            ->where('id', $this->messageId)
            ->where('status', MessageStatus::Pending->value)
            ->update([
                'status' => MessageStatus::Sending->value,
                'updated_at' => now(),
            ]);

        if ($claimed === 0) {
            return;
        }

        // Check global SMS sending kill-switch
        if (! app(\App\Settings\SmsSettings::class)->sms_enabled) {
            SmsLogger::error('sms_disabled', [
                'message_id' => $message->id,
                'user_id' => $message->user_id,
                'phone' => (string) $message->to,
                'status' => 'failed',
                'error_message' => 'SMS sending is globally disabled in SMS Settings.',
            ]);

            $this->failAndRefund($message, now(), 'SMS sending is globally disabled.');

            return;
        }

        $from = $message->sender?->sender_id ?? $batch->sender?->sender_id;
        $now = Carbon::now();

        // Resolve priority gateway chain (Priority 1 -> Priority 2 -> Priority 3)
        $routeResolver = app(\App\Services\Sms\Routing\SmsRouteResolver::class);
        $gateways = $routeResolver->resolveGateways(
            recipient: (string) $message->to,
            trafficType: 'transactional',
            sender: $message->sender ?? $batch->sender,
            preferredGateway: $gateway,
        );

        if ($gateways->isEmpty()) {
            $gateways = collect([$gateway]);
        }

        $lastError = 'No available gateway responded successfully.';

        foreach ($gateways as $attemptIndex => $currentGateway) {
            $driver = Sms::usingGateway($currentGateway)->driver($currentGateway->driver->value);

            try {
                $response = $driver->send(new SmsRequest(
                    to: (string) $message->to,
                    body: $message->body,
                    from: $from,
                    isUnicode: (bool) $message->is_unicode,
                ));

                if ($response->successful) {
                    SmsLogger::info('sms_sent', [
                        'message_id' => $message->id,
                        'user_id' => $message->user_id,
                        'gateway_id' => $currentGateway->id,
                        'phone' => (string) $message->to,
                        'gateway_name' => $currentGateway->name ?? $currentGateway->driver->value,
                        'gateway_message_id' => $response->gatewayMessageId,
                        'status' => 'sent',
                        'job_id' => $this->job?->getJobId(),
                        'queue' => $this->queue,
                    ]);

                    $message->update([
                        'gateway_id' => $currentGateway->id,
                        'status' => MessageStatus::Sent->value,
                        'sent_at' => $now,
                        'gateway_message_id' => $response->gatewayMessageId,
                        'failed_at' => null,
                        'failed_reason' => null,
                        'poll_attempt' => $response->gatewayMessageId !== null ? 1 : null,
                        'next_poll_at' => $response->gatewayMessageId !== null ? $now->copy()->addMinutes(1) : null,
                        'updated_at' => $now,
                    ]);

                    return;
                }

                $lastError = $response->errorMessage ?? 'Gateway rejected SMS';

                SmsLogger::warning('sms_gateway_failover', [
                    'message_id' => $message->id,
                    'user_id' => $message->user_id,
                    'gateway_id' => $currentGateway->id,
                    'phone' => (string) $message->to,
                    'gateway_name' => $currentGateway->name ?? $currentGateway->driver->value,
                    'status' => 'failover',
                    'error_message' => "Attempt {$attemptIndex} failed: {$lastError}. Trying next priority provider...",
                    'job_id' => $this->job?->getJobId(),
                ]);
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();

                SmsLogger::warning('sms_gateway_failover_exception', [
                    'message_id' => $message->id,
                    'user_id' => $message->user_id,
                    'gateway_id' => $currentGateway->id,
                    'phone' => (string) $message->to,
                    'gateway_name' => $currentGateway->name ?? $currentGateway->driver->value,
                    'status' => 'failover',
                    'error_message' => "Attempt {$attemptIndex} exception: {$lastError}. Trying next priority provider...",
                    'job_id' => $this->job?->getJobId(),
                ]);
            }
        }

        // If all priority gateways fail
        SmsLogger::error('sms_send_failed_all_gateways', [
            'message_id' => $message->id,
            'user_id' => $message->user_id,
            'phone' => (string) $message->to,
            'status' => 'failed',
            'error_message' => "All {$gateways->count()} priority gateway(s) failed. Final error: {$lastError}",
            'job_id' => $this->job?->getJobId(),
        ]);

        $this->failAndRefund($message, $now, $lastError);
    }

    /**
     * Marks the message as Failed and refunds its cost to the user's balance in a single transaction.
     */
    private function failAndRefund(Message $message, Carbon $now, ?string $reason): void
    {
        DB::transaction(function () use ($message, $now, $reason): void {
            $message->update([
                'status' => MessageStatus::Failed->value,
                'failed_at' => $now,
                'failed_reason' => $reason,
                'sent_at' => null,
                'updated_at' => $now,
            ]);

            if (($message->cost ?? 0) > 0) {
                /** @var User $user */
                $user = User::findOrFail($message->user_id);

                app(CreditBalance::class)->handle(
                    user: $user,
                    amount: $message->cost,
                    transactionable: $message,
                    description: 'Refund: SMS failed to send',
                );
            }
        });
    }
}
