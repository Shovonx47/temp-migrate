<?php

namespace App\Jobs;

use App\Actions\Billing\CreditBalance;
use App\Contracts\SupportsDeliveryStatus;
use App\Enums\DeliveryOutcome;
use App\Enums\MessageStatus;
use App\Models\Message;
use App\Models\User;
use App\Services\Logging\SmsLogger;
use App\Services\Sms\Data\DeliveryStatusResponse;
use App\Support\Facades\Sms;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Polls a gateway's DLR (delivery receipt) API for a single sent message.
 *
 * Pull-DLR strategy: we initiate the query to the gateway — no webhooks involved.
 *
 * Scheduling is DB-backed: send jobs write `poll_attempt` and `next_poll_at` to the
 * messages table. The `messages:poll-delivery` command finds due messages every minute
 * and dispatches this job for each one. This makes the schedule resilient to Redis
 * flushes — the command will re-pick any dropped jobs on the next tick.
 *
 * Delay schedule (set on the message after each InTransit response):
 *   Attempt 1 → 2 min   Attempt 5 → +4 hr
 *   Attempt 2 → +5 min  Attempt 6 → +12 hr
 *   Attempt 3 → +15 min Attempt 7 → give up (leave as Sent, clear poll columns)
 *   Attempt 4 → +1 hr
 *
 * The job is idempotent: if the message is no longer in the Sent state when it runs,
 * it exits silently (already resolved by another process).
 */
class PollMessageDeliveryJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 30;

    public int $tries = 1;

    /**
     * Maximum number of poll attempts per message.
     */
    public const int MAX_ATTEMPTS = 5;

    /**
     * Do not poll messages sent more than 48 hours ago — DLR data may no longer be available.
     */
    public const int MAX_AGE_HOURS = 48;

    public function __construct(
        public readonly int $messageId,
    ) {}

    public function handle(): void
    {
        $message = Message::where('id', $this->messageId)
            ->where('status', MessageStatus::Sent)
            ->whereNotNull('gateway_message_id')
            ->with(['sender.gateway', 'gateway'])
            ->first();

        if (! $message) {
            return; // Already resolved or missing gateway_message_id.
        }

        $now = Carbon::now();

        // Guard: stop polling very old messages.
        if ($message->sent_at && $message->sent_at->diffInHours($now) > self::MAX_AGE_HOURS) {
            $message->update(['poll_attempt' => null, 'next_poll_at' => null]);

            return;
        }

        $gateway = $message->gateway ?? $message->sender?->gateway;

        if (! $gateway) {
            $this->stopPolling($message, $now);

            return;
        }

        $driver = Sms::usingGateway($gateway)->driver($gateway->driver->value);

        if (! $driver instanceof SupportsDeliveryStatus) {
            $this->stopPolling($message, $now);

            return;
        }

        $dlr = $driver->fetchDeliveryStatus((string) $message->gateway_message_id, (string) $message->to);

        match ($dlr->outcome) {
            DeliveryOutcome::Delivered => $this->handleDelivered($message, $dlr, $now),
            DeliveryOutcome::Failed => $this->handleDeliveryFailed($message, $dlr, $now),
            DeliveryOutcome::InTransit => $this->handleInTransitOrFallback($message, $dlr, $now),
        };
    }

    private function handleInTransitOrFallback(Message $message, DeliveryStatusResponse $dlr, Carbon $now): void
    {
        $this->scheduleNextPoll($message, $now);
    }

    private function handleDelivered($message, DeliveryStatusResponse $dlr, Carbon $now): void
    {
        SmsLogger::info('dlr_delivered', [
            'message_id' => $message->id,
            'user_id' => $message->user_id,
            'phone' => (string) $message->to,
            'gateway_message_id' => $message->gateway_message_id,
            'status' => 'delivered',
        ]);

        $message->update([
            'status' => MessageStatus::Delivered->value,
            'delivered_at' => $dlr->deliveredAt ?? $now,
            'delivery_status' => $dlr->rawStatus,
            'delivery_response' => $dlr->raw === [] ? null : $dlr->raw,
            'poll_attempt' => null,
            'next_poll_at' => null,
            'updated_at' => $now,
        ]);
    }

    /**
     * Marks the message as Failed and refunds its cost to the user's balance.
     */
    private function handleDeliveryFailed(Message $message, DeliveryStatusResponse $dlr, Carbon $now): void
    {
        SmsLogger::error('dlr_failed', [
            'message_id' => $message->id,
            'user_id' => $message->user_id,
            'phone' => (string) $message->to,
            'gateway_message_id' => $message->gateway_message_id,
            'status' => 'failed',
            'error_message' => $dlr->errorMessage ?? $dlr->rawStatus,
        ]);

        DB::transaction(function () use ($message, $dlr, $now): void {
            $message->update([
                'status' => MessageStatus::Failed->value,
                'failed_at' => $now,
                'failed_reason' => $dlr->errorMessage ?? $dlr->rawStatus,
                'delivery_status' => $dlr->rawStatus,
                'delivery_response' => $dlr->raw === [] ? null : $dlr->raw,
                'poll_attempt' => null,
                'next_poll_at' => null,
                'updated_at' => $now,
            ]);

            if (($message->cost ?? 0) > 0) {
                app(CreditBalance::class)->handle(
                    user: User::findOrFail($message->user_id),
                    amount: $message->cost,
                    transactionable: $message,
                    description: 'Refund: SMS delivery failed',
                );
            }
        });
    }

    /**
     * Schedules the next poll by updating `poll_attempt` and `next_poll_at` on the message.
     * When the maximum attempt count is reached, retain the accepted state because
     * the gateway did not provide a conclusive delivery receipt.
     */
    private function scheduleNextPoll(Message $message, Carbon $now): void
    {
        $attempt = (int) ($message->poll_attempt ?? 1);

        if ($attempt >= self::MAX_ATTEMPTS) {
            $this->stopPolling($message, $now);

            return;
        }

        $nextAttempt = $attempt + 1;

        $message->update([
            'poll_attempt' => $nextAttempt,
            'next_poll_at' => $now->copy()->addSeconds($this->delayForAttempt($nextAttempt)),
        ]);
    }

    private function stopPolling(Message $message, Carbon $now): void
    {
        $message->update([
            'poll_attempt' => null,
            'next_poll_at' => null,
            'updated_at' => $now,
        ]);
    }

    /**
     * Returns the delay in seconds before the next poll for a given attempt number.
     *
     * @param  int  $attempt  1-based attempt number of the *next* poll being scheduled.
     */
    private function delayForAttempt(int $attempt): int
    {
        return match ($attempt) {
            1 => 60,            // 1 min (initial)
            2 => 120,           // +2 min
            3 => 120,           // +2 min (~5 min mark)
            default => 120,
        };
    }
}
