<?php

namespace App\Jobs;

use App\Enums\MessageBatchStatus;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\OperatorRateNotConfiguredException;
use App\Exceptions\SenderRequiredException;
use App\Models\Contact;
use App\Models\Message;
use App\Models\MessageBatch;
use App\Models\Sender;
use App\Models\User;
use App\Support\MessageMetrics;
use App\Support\OperatorRateResolver;
use App\Support\RecipientFileParser;
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

    public int $timeout = 0;

    public int $tries = 1;

    /** @var resource|null */
    private $failedReportHandle = null;

    private ?string $failedReportPath = null;

    private ?string $resolvedUploadPath = null;

    private bool $uploadPathResolved = false;

    private ?OperatorRateResolver $rateResolver = null;

    private bool $isMaskingSender = false;

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
            $this->initialiseBilling($batch);

            foreach ($this->iterateRecipients($parser, $cursor) as $recipient) {
                $latestCursor = $recipient['cursor'];
                $phoneRaw = (string) ($recipient['phone'] ?? '');

                if ($phoneRaw === '') {
                    $this->recordFailure($phoneRaw, 'Phone number missing', $failedCount, $processedSinceFlush);
                    $this->maybeFlush($batch, $rows, $successCount, $failedCount, $processedSinceFlush, $latestCursor);

                    continue;
                }

                $formattedNumber = $this->formatByRegex($phoneRaw);

                if ($formattedNumber === null) {
                    $this->recordFailure($phoneRaw, 'Invalid phone number format', $failedCount, $processedSinceFlush);
                    $this->maybeFlush($batch, $rows, $successCount, $failedCount, $processedSinceFlush, $latestCursor);

                    continue;
                }

                // We may use libphonenumber for fallback validation.
                // try {
                //    $phone = new PhoneNumber($phoneRaw, self::DEFAULT_COUNTRY);
                //    if (!$phone->isValid()) {
                //        $this->recordFailure($phoneRaw, 'Invalid phone number format', $failedCount, $processedSinceFlush);
                //        $this->maybeFlush($batch, $rows, $successCount, $failedCount, $processedSinceFlush, $latestCursor);
                //
                //        continue;
                //    }
                // } catch (Throwable) {
                //    $this->recordFailure($phoneRaw, 'Invalid phone number format', $failedCount, $processedSinceFlush);
                //    $this->maybeFlush($batch, $rows, $successCount, $failedCount, $processedSinceFlush, $latestCursor);
                //
                //    continue;
                // }

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

                $body = $this->resolveBody($recipient);
                $rows[] = [
                    'user_id' => $batch->user_id,
                    'campaign_id' => null,
                    'message_batch_id' => $batch->id,
                    'sender_id' => $batch->sender_id,
                    'to' => $formattedNumber,
                    'body' => $body,
                    'status' => 'pending',
                    'parts' => MessageMetrics::for($body)->parts,
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
        } catch (InsufficientBalanceException $e) {
            $this->closeFailedReport();

            $batch->update([
                'status' => MessageBatchStatus::Failed,
                'failed_report_path' => $this->failedReportPath,
                'completed_at' => now(),
            ]);
            $this->notifyInsufficientBalance($batch, $e);
        } catch (SenderRequiredException $e) {
            $this->closeFailedReport();

            $batch->update([
                'status' => MessageBatchStatus::Failed,
                'failed_report_path' => $this->failedReportPath,
                'completed_at' => now(),
            ]);
            $this->notifySenderRequired($batch);
        } catch (OperatorRateNotConfiguredException $e) {
            $this->closeFailedReport();

            $batch->update([
                'status' => MessageBatchStatus::Failed,
                'failed_report_path' => $this->failedReportPath,
                'completed_at' => now(),
            ]);
            $this->notifyOperatorRateMissing($batch, $e);
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
     *
     * @throws Throwable
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
     * Atomically prices the buffered rows, debits the user's balance, inserts the
     * messages with per-message Transaction rows, and advances the batch cursor.
     *
     * Throws InsufficientBalanceException (rolling back the entire chunk) if the
     * user can't afford this group — the outer handler marks the batch Failed
     * with the cursor preserved so a top-up + retry can resume cleanly.
     *
     * @param  array<int, array<string, mixed>>  $rows
     *
     * @throws Throwable
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

        $messageIds = [];

        DB::transaction(function () use ($batch, &$rows, $successCount, $failedCount, $latestCursor, &$messageIds): void {
            if ($rows !== []) {
                $messageIds = $this->priceInsertAndDebit($batch, $rows);
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

        if ($messageIds !== []) {
            SendBatchSmsJob::dispatch($batch, $messageIds)->onQueue('sms-sending');
        }

        $rows = [];
    }

    /**
     * Locks the user, ensures the chunk is affordable, inserts the messages,
     * writes a single debit Transaction row for the chunk, and returns the IDs
     * of the inserted messages so the caller can dispatch sending immediately.
     *
     * MySQL guarantees that LAST_INSERT_ID() returns the first auto-increment ID
     * assigned by a multi-row INSERT, with subsequent rows getting consecutive IDs.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return int[]
     */
    private function priceInsertAndDebit(MessageBatch $batch, array &$rows): array
    {
        $user = User::query()
            ->whereKey($batch->user_id)
            ->lockForUpdate()
            ->firstOrFail();

        $resolver = $this->rateResolver;

        if ($resolver === null) {
            throw new SenderRequiredException;
        }

        $totalCost = 0.0;

        foreach ($rows as &$row) {
            $rate = $resolver->resolve((string) $row['to'], $this->isMaskingSender);
            $row['rate'] = $rate;
            $row['cost'] = round($rate * (int) $row['parts'], 4);
            $totalCost = round($totalCost + $row['cost'], 4);
        }
        unset($row);

        $balanceBefore = (float) $user->balance;

        if ($balanceBefore < $totalCost) {
            throw new InsufficientBalanceException(
                required: $totalCost,
                available: $balanceBefore,
            );
        }

        $balanceAfter = round($balanceBefore - $totalCost, 4);
        $user->forceFill(['balance' => $balanceAfter])->save();

        Message::insert($rows);

        // PDO::lastInsertId() behaviour differs by driver:
        //   MySQL / MariaDB → returns the FIRST auto-increment ID assigned in a multi-row INSERT.
        //   SQLite / PostgreSQL / others → returns the LAST assigned ID.
        // Both guarantee consecutive IDs for a single INSERT statement with no concurrent writers
        // on the same table (enforced by the FOR UPDATE lock above).
        $count = count($rows);
        $anchorId = (int) DB::getPdo()->lastInsertId();
        $insertedIds = match (DB::getDriverName()) {
            'mysql', 'mariadb' => range($anchorId, $anchorId + $count - 1),
            default => range($anchorId - $count + 1, $anchorId),
        };

        $now = now();
        DB::table('transactions')->insert([
            'user_id' => $batch->user_id,
            'transactionable_type' => 'message_batch',
            'transactionable_id' => $batch->id,
            'amount' => $totalCost,
            'direction' => 'debit',
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => 'SMS batch debit ('.count($rows).' messages)',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $insertedIds;
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

    private function formatByRegex(string $raw): ?string
    {
        if (preg_match('/^(?:\+?88)?0?(1[3-9]\d{8})$/', $raw, $m)) {
            return '+880'.$m[1];
        }

        return null;
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

            $values = array_map(function ($colName) use ($row) {
                return $colName ? ($row[$colName] ?? '') : '';
            }, $variableCols);

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

    private function notifyInsufficientBalance(MessageBatch $batch, InsufficientBalanceException $e): void
    {
        $user = User::find($batch->user_id);
        if (! $user) {
            return;
        }

        $shortfall = number_format(max($e->required - $e->available, 0), 2);

        Notification::make()
            ->title('Batch paused: insufficient balance')
            ->body("You need ৳{$shortfall} more to continue. Top up your balance and retry the batch.")
            ->icon(Heroicon::Banknotes)
            ->danger()
            ->sendToDatabase($user);
    }

    private function notifySenderRequired(MessageBatch $batch): void
    {
        $user = User::find($batch->user_id);
        if (! $user) {
            return;
        }

        Notification::make()
            ->title('Batch failed: sender missing')
            ->body('This batch has no sender selected. Pricing depends on the sender type, so we cannot process it.')
            ->icon(Heroicon::ExclamationTriangle)
            ->danger()
            ->sendToDatabase($user);
    }

    private function notifyOperatorRateMissing(MessageBatch $batch, OperatorRateNotConfiguredException $e): void
    {
        $user = User::find($batch->user_id);
        if (! $user) {
            return;
        }

        $type = $e->isMasking ? 'masking' : 'non-masking';
        $operator = $e->operatorName ?? 'unknown operator';

        Notification::make()
            ->title('Batch failed: pricing not configured')
            ->body("No {$type} rate is configured for {$operator}. Please contact support.")
            ->icon(Heroicon::ExclamationTriangle)
            ->danger()
            ->sendToDatabase($user);
    }

    private function initialiseBilling(MessageBatch $batch): void
    {
        if ($batch->sender_id === null) {
            throw new SenderRequiredException;
        }

        $sender = Sender::find($batch->sender_id);
        if (! $sender || $sender->type === null) {
            throw new SenderRequiredException;
        }

        /** @var User $user */
        $user = User::findOrFail($batch->user_id);

        $this->isMaskingSender = $sender->type->isMasking();
        $this->rateResolver = OperatorRateResolver::forUser($user);
    }
}
