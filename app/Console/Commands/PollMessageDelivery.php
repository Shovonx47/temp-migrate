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

        return self::SUCCESS;
    }
}
