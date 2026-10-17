<?php

namespace App\Console\Commands\Batches;

use App\Enums\BatchSource;
use App\Enums\BatchStatus;
use App\Enums\SmsApproval;
use App\Jobs\ProcessBatchJob;
use App\Models\Batch;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('batches:send-scheduled')]
#[Description('Dispatch any scheduled batches whose scheduled_at has fallen due.')]
class SendScheduledBatches extends Command
{
    public function handle(): int
    {
        $now = now();
        $dispatched = 0;

        Batch::withoutGlobalScopes()
            ->where('status', BatchStatus::Scheduled)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now)
            ->orderBy('scheduled_at')
            ->chunkById(50, function ($batches) use (&$dispatched): void {
                foreach ($batches as $batch) {
                    try {
                        $requiresApproval = $batch->approval === SmsApproval::Pending;

                        // Atomic state update to ensure this batch cannot be picked up concurrently
                        $affected = DB::table('batches')
                            ->where('id', $batch->id)
                            ->where('status', BatchStatus::Scheduled->value)
                            ->update([
                                'status' => $requiresApproval ? BatchStatus::Pending->value : BatchStatus::Processing->value,
                                'updated_at' => now(),
                            ]);

                        if ($affected === 0) {
                            continue;
                        }

                        $batch->refresh();

                        if (! $requiresApproval) {
                            $sourceType = $batch->source_type instanceof BatchSource
                                ? $batch->source_type->value
                                : (string) $batch->source_type;

                            $sourceConfig = (array) ($batch->source_config ?? []);
                            $body = (string) $batch->body;
                            $bodyVariables = method_exists($batch, 'extractBodyVariables')
                                ? $batch->extractBodyVariables()
                                : [];

                            ProcessBatchJob::dispatch(
                                $batch,
                                $sourceType,
                                $sourceConfig,
                                $body,
                                $bodyVariables,
                            )->onQueue('batches');

                            Log::info("SendScheduledBatches: Dispatched scheduled batch #{$batch->id} to 'batches' queue.");
                        }

                        $dispatched++;
                    } catch (Throwable $e) {
                        $this->error("Failed to dispatch batch #{$batch->id}: {$e->getMessage()}");
                        Log::error("SendScheduledBatches: Failed to dispatch batch #{$batch->id}", [
                            'exception' => $e,
                        ]);

                        // Revert batch back to Scheduled so it does not get stranded in Processing
                        DB::table('batches')
                            ->where('id', $batch->id)
                            ->where('status', BatchStatus::Processing->value)
                            ->update(['status' => BatchStatus::Scheduled->value]);
                    }
                }
            });

        if ($dispatched > 0) {
            $this->info("Dispatched {$dispatched} scheduled batch(es).");
        }

        return self::SUCCESS;
    }
}
