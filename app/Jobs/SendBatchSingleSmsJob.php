<?php

namespace App\Jobs;

use App\Actions\Billing\CreditBalance;
use App\Enums\MessageStatus;
use App\Models\Message;
use App\Models\MessageBatch;
use App\Models\User;
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
 * 'sms' RateLimiter keyed by gateway ID (requests_per_minute on the Gateway model).
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
        public readonly MessageBatch $batch,
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

    public function handle(): void
    {
        $batch = $this->batch->fresh(['sender.gateway']);

        if (! $batch) {
            return;
        }

        $gateway = $batch->sender?->gateway;

        if (! $gateway) {
            return;
        }

        $message = Message::where('id', $this->messageId)
            ->where('status', MessageStatus::Pending)
            ->select(['id', 'to', 'body', 'cost', 'user_id'])
            ->first();

        // Idempotency guard: already processed or doesn't exist.
        if (! $message) {
            return;
        }

        $driver = Sms::usingGateway($gateway)->driver($gateway->driver->value);
        $now = Carbon::now();

        try {
            $response = $driver->send(new SmsRequest(
                to: (string) $message->to,
                body: $message->body,
                from: $batch->sender?->sender_id,
            ));

            if ($response->successful) {
                $message->update([
                    'status' => MessageStatus::Sent->value,
                    'sent_at' => $now,
                    'gateway_message_id' => $response->gatewayMessageId,
                    'failed_at' => null,
                    'failed_reason' => null,
                    'updated_at' => $now,
                ]);

                // Schedule a DLR poll for messages that have a gateway ID to track.
                if ($response->gatewayMessageId !== null) {
                    PollMessageDeliveryJob::dispatch($message->id, attempt: 1)
                        ->delay(now()->addMinutes(2));
                }
            } else {
                $this->failAndRefund($message, $now, $response->errorMessage);
            }
        } catch (\Throwable $e) {
            $this->failAndRefund($message, $now, $e->getMessage());
        }
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
                app(CreditBalance::class)->handle(
                    user: User::findOrFail($message->user_id),
                    amount: $message->cost,
                    transactionable: $message,
                    description: 'Refund: SMS failed to send',
                );
            }
        });
    }
}
