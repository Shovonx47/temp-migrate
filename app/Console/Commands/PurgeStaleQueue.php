<?php

namespace App\Console\Commands;

use App\Models\Batch;
use App\Models\Message;
use Illuminate\Console\Command;

class PurgeStaleQueue extends Command
{
    protected $signature = 'queue:purge-stale
                            {--minutes=8 : Purge messages/batches stuck longer than this many minutes}
                            {--dry-run : Preview what would be purged without making changes}';

    protected $description = 'Automatically purge SMS queue items that have been stuck for too long (default: 8 minutes)';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $isDryRun = $this->option('dry-run');
        $threshold = now()->subMinutes($minutes);

        $this->info("Scanning for queue items stuck more than {$minutes} minutes (since {$threshold->toDateTimeString()})...");

        // 1. Messages stuck in 'pending' or 'sending' for too long
        $staleMessagesQuery = Message::withoutGlobalScopes()
            ->whereIn('status', ['pending', 'sending'])
            ->where('updated_at', '<=', $threshold);

        $staleMessageCount = $staleMessagesQuery->count();

        // 2. Batches stuck in 'pending' or 'processing' for too long
        $staleBatchesQuery = Batch::withoutGlobalScopes()
            ->whereIn('status', ['pending', 'processing'])
            ->where('updated_at', '<=', $threshold);

        $staleBatchCount = $staleBatchesQuery->count();

        if ($isDryRun) {
            $this->warn("[DRY RUN] Would purge {$staleMessageCount} stuck message(s) and {$staleBatchCount} stuck batch(es).");

            return self::SUCCESS;
        }

        if ($staleMessageCount === 0 && $staleBatchCount === 0) {
            $this->line('✓ Queue is clean — no stale items found.');

            return self::SUCCESS;
        }

        // Purge stale messages
        if ($staleMessageCount > 0) {
            $staleMessagesQuery->update([
                'status' => 'failed',
                'failed_reason' => "Auto-purged: stuck in queue for more than {$minutes} minutes without progress",
                'updated_at' => now(),
            ]);

            $this->info("✓ Purged {$staleMessageCount} stale message(s).");
        }

        // Purge stale batches
        if ($staleBatchCount > 0) {
            $staleBatchesQuery->update([
                'status' => 'failed',
                'updated_at' => now(),
            ]);

            $this->info("✓ Purged {$staleBatchCount} stale batch(es).");
        }

        $total = $staleMessageCount + $staleBatchCount;
        $this->info("Auto-purge complete: {$total} stale queue item(s) cleared.");

        return self::SUCCESS;
    }
}
