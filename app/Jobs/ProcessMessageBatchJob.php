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

        $batch->update([
            'status' => MessageBatchStatus::Processing,
            'started_at' => now(),
            'total_recipients' => $this->computeTotalRecipients($parser),
            'valid_count' => 0,
            'invalid_count' => 0,
        ]);

        $rows = [];
        $successCount = 0;
        $failedCount = 0;
        $processedSinceFlush = 0;
        $now = now();

        try {
            foreach ($this->iterateRecipients($parser) as $recipient) {
                $phoneRaw = (string) ($recipient['phone'] ?? '');

                if ($phoneRaw === '') {
                    $this->recordFailure($batch->id, '', 'Phone number missing', $successCount, $failedCount, $processedSinceFlush);

                    continue;
                }

                if (! $this->looksLikePhone($phoneRaw)) {
                    $this->recordFailure($batch->id, $phoneRaw, 'Invalid phone number format', $successCount, $failedCount, $processedSinceFlush);

                    continue;
                }

                try {
                    $phone = new PhoneNumber($phoneRaw, self::DEFAULT_COUNTRY);
                    if (! $phone->isValid()) {
                        $this->recordFailure($batch->id, $phoneRaw, 'Invalid phone number format', $successCount, $failedCount, $processedSinceFlush);

                        continue;
                    }
                } catch (Throwable) {
                    $this->recordFailure($batch->id, $phoneRaw, 'Invalid phone number format', $successCount, $failedCount, $processedSinceFlush);

                    continue;
                }

                $missingVariables = $this->missingVariables($recipient);
                if ($missingVariables !== []) {
                    $this->recordFailure(
                        $batch->id,
                        $phoneRaw,
                        'Missing value for: '.implode(', ', $missingVariables),
                        $successCount,
                        $failedCount,
                        $processedSinceFlush,
                    );

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

                if (count($rows) >= self::BATCH_SIZE) {
                    Message::insert($rows);
                    $rows = [];
                }

                $this->maybeFlushProgress($batch->id, $successCount, $failedCount, $processedSinceFlush);
            }

            if (! empty($rows)) {
                Message::insert($rows);
            }

            $this->closeFailedReport();

            $batch->update([
                'status' => MessageBatchStatus::Completed,
                'valid_count' => $successCount,
                'invalid_count' => $failedCount,
                'failed_report_path' => $this->failedReportPath,
                'completed_at' => now(),
            ]);

            $this->notifyCompletion($batch, $successCount, $failedCount, $this->failedReportPath);
        } catch (Throwable $e) {
            $this->closeFailedReport();

            $batch->update([
                'status' => MessageBatchStatus::Failed,
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
     * @return iterable<int, array{phone: string, values: array<string, string>, raw: array<string, mixed>}>
     */
    private function iterateRecipients(RecipientFileParser $parser): iterable
    {
        return match ($this->sourceType) {
            'manual' => $this->iterateManual(),
            'upload' => $this->iterateUpload($parser),
            'group' => $this->iterateGroup(),
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

    private function maybeFlushProgress(int $batchId, int $valid, int $invalid, int &$processedSinceFlush): void
    {
        if ($processedSinceFlush < self::BATCH_SIZE) {
            return;
        }

        DB::table('message_batches')
            ->where('id', $batchId)
            ->update([
                'valid_count' => $valid,
                'invalid_count' => $invalid,
            ]);

        $processedSinceFlush = 0;
    }

    private function recordFailure(
        int $batchId,
        string $phone,
        string $reason,
        int $successCount,
        int &$failedCount,
        int &$processedSinceFlush,
    ): void {
        $this->writeFailedRow($phone, $reason);
        $failedCount++;
        $processedSinceFlush++;
        $this->maybeFlushProgress($batchId, $successCount, $failedCount, $processedSinceFlush);
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
        $this->failedReportPath = sprintf(
            'message-batches/%d/failed-recipients-%s.csv',
            $this->batch->id,
            now()->format('Ymd-His'),
        );

        $disk = Storage::disk('local');
        $disk->makeDirectory(dirname($this->failedReportPath));

        $absolutePath = $disk->path($this->failedReportPath);
        $this->failedReportHandle = fopen($absolutePath, 'w');
        fputcsv($this->failedReportHandle, ['Phone', 'Reason'], escape: '\\');
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
     * @return iterable<int, array{phone: string, values: array<string, string>, raw: array<string, mixed>}>
     */
    private function iterateManual(): iterable
    {
        foreach ($this->config['recipients'] ?? [] as $r) {
            yield [
                'phone' => (string) ($r['phone'] ?? ''),
                'values' => $r['values'] ?? [],
                'raw' => $r,
            ];
        }
    }

    /**
     * @return iterable<int, array{phone: string, values: array<string, string>, raw: array<string, mixed>}>
     */
    private function iterateUpload(RecipientFileParser $parser): iterable
    {
        $absolutePath = $this->resolveUploadPath();

        if ($absolutePath === null) {
            return;
        }

        $mapping = $this->config['mapping'] ?? [];
        $phoneCol = $mapping['phone'] ?? null;
        $variableCols = $mapping['variables'] ?? [];

        foreach ($parser->stream($absolutePath) as $row) {
            $values = [];
            foreach ($variableCols as $varName => $colName) {
                $values[$varName] = $colName ? ($row[$colName] ?? '') : '';
            }

            yield [
                'phone' => $phoneCol ? (string) ($row[$phoneCol] ?? '') : '',
                'values' => $values,
                'raw' => $row,
            ];
        }
    }

    /**
     * @return iterable<int, array{phone: string, values: array<string, string>, raw: array<string, mixed>}>
     */
    private function iterateGroup(): iterable
    {
        $groupIds = $this->config['group_ids'] ?? [];

        if (empty($groupIds)) {
            return;
        }

        $query = Contact::query()
            ->whereHas('groups', fn ($q) => $q->whereIn('groups.id', $groupIds))
            ->where('is_blocked', false);

        foreach ($query->lazy(1000) as $contact) {
            yield [
                'phone' => (string) $contact->phone,
                'values' => [],
                'raw' => ['phone' => (string) $contact->phone, 'name' => $contact->name ?? ''],
            ];
        }
    }

    /**
     * @param  array{phone: string, values: array<string, string>, raw: array<string, mixed>}  $recipient
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
     * @param  array{phone: string, values: array<string, string>, raw: array<string, mixed>}  $recipient
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
