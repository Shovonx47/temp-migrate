<?php

namespace App\Jobs;

use App\Actions\Billing\CreditBalance;
use App\Contracts\SupportsDeliveryStatus;
use App\Enums\DeliveryOutcome;
use App\Enums\MessageStatus;
use App\Models\Message;
use App\Models\User;
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
 * If the carrier has not yet produced a final status (InTransit), the job re-dispatches
 * itself with an exponential delay until attempt 7, after which polling stops.
 *
 * Delay schedule (cumulative window from sent_at):
 *   Attempt 1 → 2 min   Attempt 5 → +4 hr
 *   Attempt 2 → +5 min  Attempt 6 → +12 hr
 *   Attempt 3 → +15 min Attempt 7 → give up (leave as Sent)
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
     * Maximum number of poll attempts per message. After this the job gives up.
     */
    public const int MAX_ATTEMPTS = 7;

    /**
     * Do not poll messages sent more than 48 hours ago — DLR data may no longer be available.
     */
    public const int MAX_AGE_HOURS = 48;

    /**
     * @param  int  $messageId  The ID of the message to poll.
     * @param  int  $attempt  The 1-based poll attempt number (incremented on each retry).
     */
    public function __construct(
        public readonly int $messageId,
        public readonly int $attempt = 1,
    ) {}

    public function handle(): void
    {
        $message = Message::where('id', $this->messageId)
            ->where('status', MessageStatus::Sent)
            ->whereNotNull('gateway_message_id')
            ->with('sender.gateway')
            ->first();

        if (! $message) {
            return; // Already resolved or missing gateway_message_id.
        }

        // Guard: stop polling very old messages.
        if ($message->sent_at && $message->sent_at->diffInHours(now()) > self::MAX_AGE_HOURS) {
            return;
        }

        $gateway = $message->sender?->gateway;

        if (! $gateway) {
            return;
        }

        $driver = Sms::usingGateway($gateway)->driver($gateway->driver->value);

        if (! $driver instanceof SupportsDeliveryStatus) {
            return;
        }

        $dlr = $driver->fetchDeliveryStatus((string) $message->gateway_message_id);
        $now = Carbon::now();

        match ($dlr->outcome) {
            DeliveryOutcome::Delivered => $message->update([
                'status' => MessageStatus::Delivered->value,
                'delivered_at' => $dlr->deliveredAt ?? $now,
                'updated_at' => $now,
            ]),
            DeliveryOutcome::Failed => $this->handleDeliveryFailed($message, $dlr, $now),
            DeliveryOutcome::InTransit => $this->rescheduleIfAllowed(),
        };
    }

    /**
     * Marks the message as Failed and refunds its cost to the user's balance.
     */
    private function handleDeliveryFailed(Message $message, DeliveryStatusResponse $dlr, Carbon $now): void
    {
        DB::transaction(function () use ($message, $dlr, $now): void {
            $message->update([
                'status' => MessageStatus::Failed->value,
                'failed_at' => $now,
                'failed_reason' => $dlr->errorMessage ?? $dlr->rawStatus,
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

    private function rescheduleIfAllowed(): void
    {
        if ($this->attempt >= self::MAX_ATTEMPTS) {
            return; // Exhausted retries — leave the message as Sent.
        }

        static::dispatch($this->messageId, $this->attempt + 1)
            ->delay(now()->addSeconds($this->delayForAttempt($this->attempt + 1)));
    }

    /**
     * Returns the delay in seconds for a given attempt number.
     *
     * @param  int  $attempt  1-based attempt number (the *next* attempt being scheduled).
     */
    private function delayForAttempt(int $attempt): int
    {
        return match ($attempt) {
            1 => 120,           //  2 min  (initial dispatch from send jobs)
            2 => 300,           //  5 min
            3 => 900,           // 15 min
            4 => 3_600,         //  1 hr
            5 => 14_400,        //  4 hr
            6 => 43_200,        // 12 hr
            default => 43_200,  // 12 hr  (attempt 7+, though 7 is the cap)
        };
    }
}
