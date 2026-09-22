<?php

namespace App\Console\Commands;

use App\Enums\MessageStatus;
use App\Jobs\PollMessageDeliveryJob;
use App\Models\Message;
use Illuminate\Console\Command;

/**
 * Dispatches DLR poll jobs for messages that are due for a status check.
 *
 * Runs every minute via the scheduler. Because scheduling state is stored in the
 * `poll_attempt` and `next_poll_at` columns on the messages table, this command
 * recovers automatically after a Redis flush — no manual intervention required.
 *
 * A chunk size cap prevents the queue from being flooded when many messages are
 * due at the same time (e.g. after a Redis flush or a long server downtime).
 */
class PollMessageDelivery extends Command
{
    protected $signature = 'messages:poll-delivery
                            {--chunk=50 : Maximum number of jobs to dispatch per run}';

    protected $description = 'Dispatch DLR poll jobs for messages that are due for a delivery status check';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));

        $dispatched = 0;

        Message::query()
            ->where('status', MessageStatus::Sent)
            ->whereNotNull('gateway_message_id')
            ->whereNotNull('next_poll_at')
            ->where('next_poll_at', '<=', now())
            ->where('sent_at', '>=', now()->subHours(PollMessageDeliveryJob::MAX_AGE_HOURS))
            ->orderBy('next_poll_at')
            ->limit($chunk)
            ->pluck('id')
            ->each(function (int $id) use (&$dispatched): void {
                PollMessageDeliveryJob::dispatch($id);
                $dispatched++;
            });

        if ($dispatched > 0) {
            $this->line("Dispatched {$dispatched} DLR poll job(s).");
        }

        // Fallback: Transition any sent messages older than 5 minutes to Delivered.
        // Uses a two-step ID-scoped approach to avoid gap locks under REPEATABLE READ.
        // A broad WHERE-based UPDATE would take range/gap locks on the messages table,
        // blocking unrelated FK-referencing inserts (sms_logs, batches) for seconds.
        $autoDeliverIds = Message::query()
            ->where('status', MessageStatus::Sent)
            ->where('sent_at', '<=', now()->subMinutes(5))
            ->pluck('id');

        $autoDelivered = 0;
        foreach ($autoDeliverIds->chunk(200) as $idChunk) {
            $autoDelivered += Message::query()
                ->whereIn('id', $idChunk)
                ->where('status', MessageStatus::Sent)
                ->update([
                    'status' => MessageStatus::Delivered->value,
                    'delivered_at' => now(),
                    'poll_attempt' => null,
                    'next_poll_at' => null,
                    'updated_at' => now(),
                ]);
        }

        if ($autoDelivered > 0) {
            $this->line("Auto-marked {$autoDelivered} message(s) as delivered via 5-min fallback.");
        }

        return self::SUCCESS;
    }
}
