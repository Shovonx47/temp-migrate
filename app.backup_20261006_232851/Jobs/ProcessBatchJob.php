<?php

namespace App\Jobs;

use App\Enums\BatchSource;
use App\Enums\BatchStatus;
use App\Enums\MessageSource;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\OperatorRateNotConfiguredException;
use App\Exceptions\SenderRequiredException;
use App\Models\Batch;
use App\Models\Contact;
use App\Models\Message;
use App\Models\Sender;
use App\Models\User;
use App\Services\Billing\DippingChargeResolver;
use App\Support\GatewayCostResolver;
use App\Support\MessageMetrics;
use App\Support\OperatorRateResolver;
use App\Support\RecipientFileParser;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

class ProcessBatchJob implements ShouldBeUnique, ShouldQueue
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

    private ?GatewayCostResolver $gatewayCostResolver = null;

    private ?DippingChargeResolver $dippingChargeResolver = null;

    private ?int $gatewayId = null;

    private bool $isMaskingSender = false;

    private bool $usesBulkSending = false;

    /** @var array<string, ?Sender> */
    private array $senderCache = [];

    /**
     * @param  array<string, mixed>  $config
     *                                        Source-specific config:
     *                                        - manual: ['recipients' => array<int, array{phone: string, values: array<string,string>}>]
     *                                        - upload: ['file_path' => string, 'disk' => string, 'mapping' => array{phone: string, variables: array<string,string>}]
     *                                        - group:  ['group_ids' => int[]]
     * @param  string[]  $bodyVariables
     */
    public function __construct(
        public readonly Batch $batch,
        public readonly string $sourceType,
        public readonly array $config,
        public readonly string $body,
        public readonly array $bodyVariables,
        public readonly bool $syncSending = false,
    ) {
        $this->queue = 'batches';
    }

    public function uniqueId(): string
    {
        return 'batch:'.$this->batch->id;
    }

    public function handle(RecipientFileParser $parser): void
    {
        $batch = $this->batch->fresh();

        if (! $batch) {
            return;
        }

        // Concurrent retry guard: only proceed if batch is in Processing state.
        if ($batch->status !== BatchStatus::Processing) {
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

                $senderInfo = $this->resolveSenderForRow($recipient, $batch);
                if ($senderInfo === null) {
                    $rawSender = $this->extractRawSender($recipient['raw'] ?? []);
                    $this->recordFailure(
                        $phoneRaw,
                        "Sender CLI '{$rawSender}' is not registered or not enabled for this account",
                        $failedCount,
                        $processedSinceFlush,
                    );
                    $this->maybeFlush($batch, $rows, $successCount, $failedCount, $processedSinceFlush, $latestCursor);

                    continue;
                }

                $body = $this->resolveBody($recipient);
                $metrics = MessageMetrics::for($body);
                $rows[] = [
                    'user_id' => $batch->user_id,
                    'campaign_id' => null,
                    'batch_id' => $batch->id,
                    'sender_id' => $senderInfo['sender_id'],
                    'gateway_id' => $senderInfo['gateway_id'],
                    'bill_msisdn' => $senderInfo['bill_msisdn'] ?? null,
                    '_is_masking' => $senderInfo['is_masking'],
                    'to' => $formattedNumber,
                    'body' => $body,
                    'status' => 'pending',
                    'source' => $batch->source_type === BatchSource::API
                        ? MessageSource::API->value
                        : MessageSource::Web->value,
                    'source_ip' => $batch->source_ip ?? $batch->source_config['ip'] ?? null,
                    'operator_name' => $this->rateResolver?->operatorNameFor($formattedNumber),
                    'tariff_name' => $this->rateResolver?->getPlanName(),
                    'parts' => $metrics->parts,
                    'is_unicode' => $metrics->encoding === MessageMetrics::UCS2,
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
                'status' => BatchStatus::Completed,
                'failed_report_path' => $this->failedReportPath,
                'completed_at' => now(),
            ]);

            $this->notifyCompletion($batch, $successCount, $failedCount, $this->failedReportPath);
        } catch (InsufficientBalanceException $e) {
            $this->closeFailedReport();

            $batch->update([
                'status' => BatchStatus::Failed,
                'failed_report_path' => $this->failedReportPath,
                'completed_at' => now(),
            ]);
            $this->notifyInsufficientBalance($batch, $e);
        } catch (SenderRequiredException $e) {
            $this->closeFailedReport();

            $batch->update([
                'status' => BatchStatus::Failed,
                'failed_report_path' => $this->failedReportPath,
                'completed_at' => now(),
            ]);
            $this->notifySenderRequired($batch);
        } catch (OperatorRateNotConfiguredException $e) {
            $this->closeFailedReport();

            $batch->update([
                'status' => BatchStatus::Failed,
                'failed_report_path' => $this->failedReportPath,
                'completed_at' => now(),
            ]);
            $this->notifyOperatorRateMissing($batch, $e);
        } catch (Throwable $e) {
            $this->closeFailedReport();

            // Persist the failed-report path so a retry can append to it.
            $batch->update([
                'status' => BatchStatus::Failed,
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
        if ($batch && $batch->status !== BatchStatus::Failed) {
            $batch->update([
                'status' => BatchStatus::Failed,
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
        Batch $batch,
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
        Batch $batch,
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

            DB::table('batches')->where('id', $batch->id)->update($updates);
        });

        if ($messageIds !== []) {
            if ($this->usesBulkSending) {
                foreach (array_chunk($messageIds, 999) as $chunkIds) {
                    if ($this->syncSending) {
                        SendBatchBulkSmsJob::dispatchSync($batch, $chunkIds);
                    } else {
                        SendBatchBulkSmsJob::dispatch($batch, $chunkIds);
                    }
                }
            } else {
                foreach ($messageIds as $id) {
                    if ($this->syncSending) {
                        SendBatchSingleSmsJob::dispatchSync($batch, $id);
                    } else {
                        SendBatchSingleSmsJob::dispatch($batch, $id);
                    }
                }
            }
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
    private function priceInsertAndDebit(Batch $batch, array &$rows): array
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
            $isMasking = $row['_is_masking'] ?? $this->isMaskingSender;
            unset($row['_is_masking']);

            $rate = $resolver->resolve((string) $row['to'], $isMasking);
            $row['rate'] = $rate;
            $row['cost'] = round($rate * (int) $row['parts'], 4);
            $totalCost = round($totalCost + $row['cost'], 4);

            $operatorRate = $this->gatewayCostResolver?->resolveOperatorCost((string) $row['to'], $isMasking) ?? 0.0;
            $operatorCost = round($operatorRate * (int) $row['parts'], 4);
            $row['operator_cost'] = $operatorCost;

            $dipping = $this->dippingChargeResolver?->resolve(
                (string) $row['to'],
                $isMasking,
                $operatorCost,
                (int) $row['parts'],
            ) ?? ['charge' => null, 'rate' => 0.0, 'cost' => 0.0];

            $row['primary_dipping_rate'] = $dipping['rate'];
            $row['primary_dipping_cost'] = $dipping['cost'];
            $row['dipping_charge_id'] = $dipping['charge']?->id;
            $row['was_third_party_dipped'] = false;
            $row['third_party_dipping_cost'] = null;
            $row['third_party_dipping_rate'] = null;
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
            'manual', 'api' => $this->iterateManual($cursor),
            'upload' => $this->iterateUpload($parser, $cursor),
            'group' => $this->iterateGroup($cursor),
            default => [],
        };
    }

    private function computeTotalRecipients(RecipientFileParser $parser): int
    {
        return match ($this->sourceType) {
            'manual', 'api' => count($this->config['recipients'] ?? []),
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
                'batches/%d/failed-recipients-%s.csv',
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

            $phone = $phoneCol ? (string) ($row[$phoneCol] ?? '') : '';
            if ($phone === '') {
                foreach (['phone', 'msisdn', 'mobile', 'recipient', 'destination_msisdn', 'number', 'to', 'phone_number'] as $key) {
                    if (! empty($row[$key])) {
                        $phone = (string) $row[$key];
                        break;
                    }
                }
                if ($phone === '' && ! empty($row)) {
                    foreach ($row as $val) {
                        $valClean = preg_replace('/[\s-]/', '', (string) $val) ?? '';
                        if (preg_match('/^(?:\+?88)?0?1[3-9]\d{8}$/', $valClean)) {
                            $phone = $valClean;
                            break;
                        }
                    }
                }
            }

            $values = array_map(function ($colName) use ($row) {
                return $colName ? ($row[$colName] ?? '') : '';
            }, $variableCols);

            yield [
                'cursor' => $rowIndex,
                'phone' => $phone,
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

    private function notifyCompletion(Batch $batch, int $sent, int $failed, ?string $reportPath): void
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

    private function notifyFailure(Batch $batch, Throwable $e): void
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

    private function notifyInsufficientBalance(Batch $batch, InsufficientBalanceException $e): void
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

    private function notifySenderRequired(Batch $batch): void
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

    private function notifyOperatorRateMissing(Batch $batch, OperatorRateNotConfiguredException $e): void
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

    private function initialiseBilling(Batch $batch): void
    {
        if ($batch->sender_id === null) {
            throw new SenderRequiredException;
        }

        $sender = Sender::with('gateway')->find($batch->sender_id);
        if (! $sender || $sender->type === null) {
            throw new SenderRequiredException;
        }

        /** @var User $user */
        $user = User::findOrFail($batch->user_id);

        $this->isMaskingSender = $sender->type->isMasking();
        $this->rateResolver = OperatorRateResolver::forUser($user);
        $this->usesBulkSending = ! $batch->hasBodyVariables() && ($sender->gateway?->supports_bulk ?? false);
        $this->gatewayId = $sender->gateway_id;

        if ($sender->gateway !== null) {
            $this->gatewayCostResolver = GatewayCostResolver::forGateway($sender->gateway);
            $this->dippingChargeResolver = DippingChargeResolver::forGateway($sender->gateway);
        }
    }

    /**
     * @return array{sender_id: int|null, gateway_id: int|null, is_masking: bool}|null
     */
    private function resolveSenderForRow(array $recipient, Batch $batch): ?array
    {
        $raw = $recipient['raw'] ?? [];
        $cli = $this->extractRawSender($raw);

        if ($cli === null || $cli === '') {
            return [
                'sender_id' => $batch->sender_id,
                'gateway_id' => $this->gatewayId,
                'bill_msisdn' => $this->resolveIptspBillMsisdn($batch->sender, $batch->sender?->sender_id),
                'is_masking' => $this->isMaskingSender,
            ];
        }

        if (array_key_exists($cli, $this->senderCache)) {
            $cached = $this->senderCache[$cli];
            if ($cached === null) {
                return null;
            }

            return [
                'sender_id' => $cached->id,
                'gateway_id' => $cached->gateway_id,
                'bill_msisdn' => $this->resolveIptspBillMsisdn($cached, $cached->sender_id),
                'is_masking' => $cached->type?->isMasking() ?? true,
            ];
        }

        $sender = Sender::with('gateway')
            ->where('sender_id', $cli)
            ->where('is_enabled', true)
            ->where(function ($q) use ($batch) {
                $q->whereHas('users', fn ($uq) => $uq->where('users.id', $batch->user_id))
                    ->orWhereNull('gateway_id')
                    ->orWhereHas('gateway', fn ($gq) => $gq->where('is_enabled', true));
            })
            ->first();

        $this->senderCache[$cli] = $sender;

        if (! $sender) {
            return null;
        }

        return [
            'sender_id' => $sender->id,
            'gateway_id' => $sender->gateway_id,
            'bill_msisdn' => $this->resolveIptspBillMsisdn($sender, $sender->sender_id),
            'is_masking' => $sender->type?->isMasking() ?? true,
        ];
    }

    private function resolveIptspBillMsisdn(?Sender $sender, ?string $senderId): ?string
    {
        if ($sender === null || $senderId === null || $senderId === '') {
            return null;
        }

        $apiType = strtolower(trim((string) data_get($sender->gateway?->credentials, 'api_type', '')));

        if ($apiType !== 'iptsp') {
            return null;
        }

        $digits = preg_replace('/\\D+/', '', $senderId) ?? '';

        if (preg_match('/^(?:880|0)?(1[3-9]\\d{8})$/', $digits, $matches)) {
            return '880'.$matches[1];
        }

        return null;
    }

    private function extractRawSender(array $raw): ?string
    {
        foreach (['cli', 'sender', 'sender_id', 'masking', 'sender_name', 'sender id', 'cli name', 'senderid'] as $k) {
            if (! empty($raw[$k])) {
                return trim((string) $raw[$k]);
            }
        }

        return null;
    }
}
