<?php

namespace App\Jobs;

use App\Enums\MessageBatchStatus;
use App\Models\Contact;
use App\Models\Message;
use App\Models\MessageBatch;
use App\Models\User;
use App\Services\RecipientFileParser;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

class ProcessMessageBatchJob implements ShouldQueue
{
    use Queueable;

    private const int BATCH_SIZE = 1000;

    private const string DEFAULT_COUNTRY = 'BD';

    private const string BD_PHONE_PATTERN = '/^(?:\+?88)?01[3-9]\d{8}$/';

    public int $timeout = 0;

    public int $tries = 1;

    /** @var resource|null */
    private $failedReportHandle = null;

    private ?string $failedReportPath = null;

    private ?string $resolvedUploadPath = null;

    private bool $uploadPathResolved = false;

    /**
     * @param  array<string, mixed>  $config
     *                                        Source-specific config:
     *                                        - manual: ['recipients' => array<int, array{phone: string, values: array<string,string>}>]
     *                                        - upload: ['file_path' => string, 'disk' => string, 'mapping' => array{phone: string, variables: array<string,string>}]
     *                                        - group:  ['group_ids' => int[]]
     * @param  string[]  $bodyVariables
     */
    public function __construct(
        public readonly MessageBatch $batch,
        public readonly string $sourceType,
        public readonly array $config,
        public readonly string $body,
        public readonly array $bodyVariables,
    ) {}

    public function handle(RecipientFileParser $parser): void
    {
        $batch = $this->batch->fresh();

        if (! $batch) {
            return;
        }

        // Concurrent retry guard: only proceed if batch is in Processing state.
        if ($batch->status !== MessageBatchStatus::Processing) {
            return;
        }

        $isResume = $batch->last_processed_cursor !== null;
        $cursor = $isResume ? (int) $batch->last_processed_cursor : null;

        $batch->update([
            'started_at' => now(),
            'total_recipients' => $this->computeTotalRecipients($parser),
            'valid_count' => $isResume ? $batch->valid_count : 0,
            'invalid_count' => $isResume ? $batch->invalid_count : 0,
        ]);

        // Reuse existing failed-report path on retry; create a new one on first run.
        $this->failedReportPath = $isResume ? $batch->failed_report_path : null;

        $rows = [];
        $successCount = (int) $batch->valid_count;
        $failedCount = (int) $batch->invalid_count;
        $processedSinceFlush = 0;
        $latestCursor = $cursor;
        $now = now();

        try {
            foreach ($this->iterateRecipients($parser, $cursor) as $recipient) {
                $latestCursor = $recipient['cursor'];
                $phoneRaw = (string) ($recipient['phone'] ?? '');

                if ($phoneRaw === '') {
                    $this->recordFailure($phoneRaw, 'Phone number missing', $failedCount, $processedSinceFlush);
                    $this->maybeFlush($batch, $rows, $successCount, $failedCount, $processedSinceFlush, $latestCursor);

                    continue;
                }

                if (! $this->looksLikePhone($phoneRaw)) {
                    $this->recordFailure($phoneRaw, 'Invalid phone number format', $failedCount, $processedSinceFlush);
                    $this->maybeFlush($batch, $rows, $successCount, $failedCount, $processedSinceFlush, $latestCursor);

                    continue;
                }

                try {
                    $phone = new PhoneNumber($phoneRaw, self::DEFAULT_COUNTRY);
                    if (! $phone->isValid()) {
                        $this->recordFailure($phoneRaw, 'Invalid phone number format', $failedCount, $processedSinceFlush);
                        $this->maybeFlush($batch, $rows, $successCount, $failedCount, $processedSinceFlush, $latestCursor);

                        continue;
                    }
                } catch (Throwable) {
                    $this->recordFailure($phoneRaw, 'Invalid phone number format', $failedCount, $processedSinceFlush);
                    $this->maybeFlush($batch, $rows, $successCount, $failedCount, $processedSinceFlush, $latestCursor);

                    continue;
                }

                $missingVariables = $this->missingVariables($recipient);
                if ($missingVariables !== []) {
                    $this->recordFailure(
                        $phoneRaw,
                        'Missing value for: '.implode(', ', $missingVariables),
                        $failedCount,
                        $processedSinceFlush,
                    );
                    $this->maybeFlush($batch, $rows, $successCount, $failedCount, $processedSinceFlush, $latestCursor);

                    continue;
                }

                $rows[] = [
                    'user_id' => $batch->user_id,
                    'campaign_id' => null,
                    'message_batch_id' => $batch->id,
                    'sender_id' => $batch->sender_id,
                    'to' => $phone->formatE164(),
                    'body' => $this->resolveBody($recipient),
                    'status' => 'pending',
                    'cost' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $successCount++;
                $processedSinceFlush++;

                $this->maybeFlush($batch, $rows, $successCount, $failedCount, $processedSinceFlush, $latestCursor);
            }

            $this->flush($batch, $rows, $successCount, $failedCount, $latestCursor);
            $this->closeFailedReport();

            $batch->update([
                'status' => MessageBatchStatus::Completed,
                'failed_report_path' => $this->failedReportPath,
                'completed_at' => now(),
            ]);

            $this->notifyCompletion($batch, $successCount, $failedCount, $this->failedReportPath);
        } catch (Throwable $e) {
            $this->closeFailedReport();

            // Persist the failed-report path so a retry can append to it.
            $batch->update([
                'status' => MessageBatchStatus::Failed,
                'failed_report_path' => $this->failedReportPath,
                'completed_at' => now(),
            ]);
            $this->notifyFailure($batch, $e);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        $this->closeFailedReport();

        $batch = $this->batch->fresh();
        if ($batch && $batch->status !== MessageBatchStatus::Failed) {
            $batch->update([
                'status' => MessageBatchStatus::Failed,
                'completed_at' => now(),
            ]);
            $this->notifyFailure($batch, $e);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function maybeFlush(
        MessageBatch $batch,
        array &$rows,
        int $successCount,
        int $failedCount,
        int &$processedSinceFlush,
        ?int $latestCursor,
    ): void {
        if ($processedSinceFlush < self::BATCH_SIZE) {
            return;
        }

        $this->flush($batch, $rows, $successCount, $failedCount, $latestCursor);
        $processedSinceFlush = 0;
    }

    /**
     * Atomically inserts buffered message rows and advances the cursor/counts on the batch.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function flush(
        MessageBatch $batch,
        array &$rows,
        int $successCount,
        int $failedCount,
        ?int $latestCursor,
    ): void {
        if ($rows === [] && $latestCursor === null) {
            return;
        }

        DB::transaction(function () use ($batch, &$rows, $successCount, $failedCount, $latestCursor): void {
            if ($rows !== []) {
                Message::insert($rows);
            }

            $updates = [
                'valid_count' => $successCount,
                'invalid_count' => $failedCount,
            ];

            if ($latestCursor !== null) {
                $updates['last_processed_cursor'] = $latestCursor;
            }

            DB::table('message_batches')->where('id', $batch->id)->update($updates);
        });

        $rows = [];
    }

    /**
     * @return iterable<int, array{cursor: int, phone: string, values: array<string, string>, raw: array<string, mixed>}>
     */
    private function iterateRecipients(RecipientFileParser $parser, ?int $cursor): iterable
    {
        return match ($this->sourceType) {
            'manual' => $this->iterateManual($cursor),
            'upload' => $this->iterateUpload($parser, $cursor),
            'group' => $this->iterateGroup($cursor),
            default => [],
        };
    }

    private function computeTotalRecipients(RecipientFileParser $parser): int
    {
        return match ($this->sourceType) {
            'manual' => count($this->config['recipients'] ?? []),
            'upload' => $this->countUploadRecipients($parser),
            'group' => $this->countGroupRecipients(),
            default => 0,
        };
    }

    private function countUploadRecipients(RecipientFileParser $parser): int
    {
        $path = $this->resolveUploadPath();

        return $path === null ? 0 : $parser->countRecipients($path);
    }

    private function countGroupRecipients(): int
    {
        $groupIds = $this->config['group_ids'] ?? [];

        if (empty($groupIds)) {
            return 0;
        }

        return Contact::query()
            ->whereHas('groups', fn ($q) => $q->whereIn('groups.id', $groupIds))
            ->where('is_blocked', false)
            ->count();
    }

    private function recordFailure(
        string $phone,
        string $reason,
        int &$failedCount,
        int &$processedSinceFlush,
    ): void {
        $this->writeFailedRow($phone, $reason);
        $failedCount++;
        $processedSinceFlush++;
    }

    private function writeFailedRow(string $phone, string $reason): void
    {
        if ($this->failedReportHandle === null) {
            $this->openFailedReport();
        }

        fputcsv($this->failedReportHandle, [$phone, $reason], escape: '\\');
    }

    private function openFailedReport(): void
    {
        $disk = Storage::disk('local');

        $isAppend = $this->failedReportPath !== null && $disk->exists($this->failedReportPath);

        if ($this->failedReportPath === null) {
            $this->failedReportPath = sprintf(
                'message-batches/%d/failed-recipients-%s.csv',
                $this->batch->id,
                now()->format('Ymd-His'),
            );
        }

        $disk->makeDirectory(dirname($this->failedReportPath));
        $absolutePath = $disk->path($this->failedReportPath);

        $this->failedReportHandle = fopen($absolutePath, $isAppend ? 'a' : 'w');

        if (! $isAppend) {
            fputcsv($this->failedReportHandle, ['Phone', 'Reason'], escape: '\\');
        }
    }

    private function closeFailedReport(): void
    {
        if ($this->failedReportHandle !== null) {
            fclose($this->failedReportHandle);
            $this->failedReportHandle = null;
        }
    }

    private function looksLikePhone(string $raw): bool
    {
        $cleaned = preg_replace('/[\s\-().]/', '', $raw) ?? '';

        return preg_match(self::BD_PHONE_PATTERN, $cleaned) === 1;
    }

    private function resolveUploadPath(): ?string
    {
        if ($this->uploadPathResolved) {
            return $this->resolvedUploadPath;
        }

        $disk = $this->config['disk'] ?? 'local';
        $relativePath = (string) ($this->config['file_path'] ?? '');

        $this->uploadPathResolved = true;

        if ($relativePath === '' || ! Storage::disk($disk)->exists($relativePath)) {
            return $this->resolvedUploadPath = null;
        }

        return $this->resolvedUploadPath = Storage::disk($disk)->path($relativePath);
    }

    /**
     * @return iterable<int, array{cursor: int, phone: string, values: array<string, string>, raw: array<string, mixed>}>
     */
    private function iterateManual(?int $cursor): iterable
    {
        $recipients = $this->config['recipients'] ?? [];
        $startIndex = $cursor === null ? 0 : $cursor + 1;

        for ($i = $startIndex; $i < count($recipients); $i++) {
            $r = $recipients[$i];
            yield [
                'cursor' => $i,
                'phone' => (string) ($r['phone'] ?? ''),
                'values' => $r['values'] ?? [],
                'raw' => $r,
            ];
        }
    }

    /**
     * @return iterable<int, array{cursor: int, phone: string, values: array<string, string>, raw: array<string, mixed>}>
     */
    private function iterateUpload(RecipientFileParser $parser, ?int $cursor): iterable
    {
        $absolutePath = $this->resolveUploadPath();

        if ($absolutePath === null) {
            return;
        }

        $mapping = $this->config['mapping'] ?? [];
        $phoneCol = $mapping['phone'] ?? null;
        $variableCols = $mapping['variables'] ?? [];

        $rowIndex = 0;
        $startIndex = $cursor === null ? 0 : $cursor + 1;

        foreach ($parser->stream($absolutePath) as $row) {
            if ($rowIndex < $startIndex) {
                $rowIndex++;

                continue;
            }

            $values = [];
            foreach ($variableCols as $varName => $colName) {
                $values[$varName] = $colName ? ($row[$colName] ?? '') : '';
            }

            yield [
                'cursor' => $rowIndex,
                'phone' => $phoneCol ? (string) ($row[$phoneCol] ?? '') : '',
                'values' => $values,
                'raw' => $row,
            ];

            $rowIndex++;
        }
    }

    /**
     * @return iterable<int, array{cursor: int, phone: string, values: array<string, string>, raw: array<string, mixed>}>
     */
    private function iterateGroup(?int $cursor): iterable
    {
        $groupIds = $this->config['group_ids'] ?? [];

        if (empty($groupIds)) {
            return;
        }

        $query = Contact::query()
            ->whereHas('groups', fn ($q) => $q->whereIn('groups.id', $groupIds))
            ->where('is_blocked', false)
            ->orderBy('id');

        if ($cursor !== null) {
            $query->where('id', '>', $cursor);
        }

        foreach ($query->lazy(1000) as $contact) {
            yield [
                'cursor' => (int) $contact->id,
                'phone' => (string) $contact->phone,
                'values' => [],
                'raw' => ['phone' => (string) $contact->phone, 'name' => $contact->name ?? ''],
            ];
        }
    }

    /**
     * @param  array{cursor: int, phone: string, values: array<string, string>, raw: array<string, mixed>}  $recipient
     * @return string[]
     */
    private function missingVariables(array $recipient): array
    {
        if ($this->sourceType === 'group') {
            return [];
        }

        $missing = [];
        foreach ($this->bodyVariables as $var) {
            $value = $recipient['values'][$var] ?? '';
            if (trim((string) $value) === '') {
                $missing[] = $var;
            }
        }

        return $missing;
    }

    /**
     * @param  array{cursor: int, phone: string, values: array<string, string>, raw: array<string, mixed>}  $recipient
     */
    private function resolveBody(array $recipient): string
    {
        if ($this->sourceType === 'group') {
            return $this->body;
        }

        $body = $this->body;
        foreach ($this->bodyVariables as $var) {
            $body = str_replace('{'.$var.'}', $recipient['values'][$var] ?? '', $body);
        }

        return $body;
    }

    private function notifyCompletion(MessageBatch $batch, int $sent, int $failed, ?string $reportPath): void
    {
        $user = User::find($batch->user_id);
        if (! $user) {
            return;
        }

        $title = "Batch processed: {$sent} message".($sent === 1 ? '' : 's').' ready to send';
        $body = $failed > 0
            ? "{$failed} recipient".($failed === 1 ? '' : 's').' were skipped. Download the report for details.'
            : 'All recipients were processed successfully.';

        $notification = Notification::make()
            ->title($title)
            ->body($body)
            ->icon(Heroicon::PaperAirplane);

        $failed > 0 ? $notification->warning() : $notification->success();

        if ($reportPath !== null) {
            $notification->actions([
                Action::make('download_report')
                    ->label('Download failed recipients')
                    ->icon(Heroicon::ArrowDownTray)
                    ->url(route('customer.messages.failed-report', [
                        'batch' => $batch->id,
                        'filename' => basename($reportPath),
                    ]))
                    ->markAsRead()
                    ->openUrlInNewTab(),
            ]);
        }

        $notification->sendToDatabase($user);
    }

    private function notifyFailure(MessageBatch $batch, Throwable $e): void
    {
        $user = User::find($batch->user_id);
        if (! $user) {
            return;
        }

        Notification::make()
            ->title('Message batch processing failed')
            ->body($e->getMessage())
            ->icon(Heroicon::ExclamationTriangle)
            ->danger()
            ->sendToDatabase($user);
    }
}
