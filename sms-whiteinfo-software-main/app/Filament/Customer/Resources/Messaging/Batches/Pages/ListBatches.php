<?php

namespace App\Filament\Customer\Resources\Messaging\Batches\Pages;

use App\Enums\BatchSource;
use App\Enums\MessageStatus;
use App\Enums\TransactionableType;
use App\Enums\TransactionDirection;
use App\Filament\Customer\Resources\Messaging\Batches\BatchResource;
use App\Filament\Customer\Resources\Messaging\Messages\MessageResource;
use App\Models\Message;
use App\Models\SmsLog;
use App\Models\Transaction;
use App\Services\ReportExportService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class ListBatches extends ListRecords
{
    protected static string $resource = BatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportAs')
                ->label('Export As')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->modalHeading('Export Sent Message Logs')
                ->modalDescription('Select your preferred report format to download.')
                ->modalSubmitActionLabel('Download Report')
                ->schema([
                    Radio::make('format')
                        ->label('Select Format')
                        ->options([
                            'excel' => 'Excel / CSV Spreadsheet (.csv)',
                            'pdf' => 'PDF Document (.pdf)',
                        ])
                        ->default('excel')
                        ->required(),
                ])
                ->action(function (array $data) {
                    return ($data['format'] ?? 'excel') === 'pdf'
                        ? $this->exportPdf()
                        : $this->exportExcel();
                }),
        ];
    }

    public function exportExcel()
    {
        $query = $this->getFilteredTableQuery()->latest('id');

        $headers = [
            'Recipient Number',
            'Sender ID',
            'Content',
            'SMS Type',
            'Cost (BDT)',
            'Previous Balance',
            'Next Balance',
            'SMS Count',
            'Status',
            'Failure Reason',
            'Date & Time',
        ];

        $rows = $query->lazy(500)->map(function ($msg) {
            return [
                $msg->to,
                $msg->sender->sender_id ?? '',
                $msg->body,
                $this->resolveSmsType($msg),
                number_format($msg->cost ?? 0, 4),
                $this->resolvePreviousBalance($msg) !== null ? number_format($this->resolvePreviousBalance($msg), 2) : '',
                $this->resolveNextBalance($msg) !== null ? number_format($this->resolveNextBalance($msg), 2) : '',
                $msg->parts ?? 1,
                $msg->status->value ?? $msg->status,
                $this->resolveFailureReason($msg) ?? '',
                ($msg->delivered_at ?? $msg->sent_at ?? $msg->failed_at ?? $msg->created_at)?->format('Y-m-d H:i:s'),
            ];
        });

        $filename = 'sent-message-logs-'.now()->format('Y-m-d-His').'.csv';

        return ReportExportService::downloadCsv($filename, $headers, $rows);
    }

    public function exportPdf()
    {
        $user = auth()->user();
        $records = $this->getFilteredTableQuery()->latest('id')->limit(500)->get();

        $data = [
            'title' => 'Sent Message Logs Report',
            'user' => $user,
            'records' => $records,
            'period' => 'Generated '.now()->format('d M Y, h:i A'),
        ];

        $filename = 'sent-message-logs-'.now()->format('Y-m-d-His').'.pdf';

        return ReportExportService::downloadPdf($filename, 'reports.pdf.messages-report', $data);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Sent Message Logs')
            ->defaultSort('id', 'desc')
            ->poll('30s')
            ->columns([
                TextColumn::make('to')
                    ->label('Recipient Number')
                    ->searchable(),
                TextColumn::make('body')
                    ->label('Content')
                    ->limit(50)
                    ->tooltip(fn (Message $record): string => $record->body)
                    ->searchable(),
                TextColumn::make('sender.sender_id')
                    ->label('Sender ID')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('sms_type')
                    ->label('SMS Type')
                    ->state(fn (Message $record): string => $this->resolveSmsType($record))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Promotional' ? 'warning' : 'success'),
                TextColumn::make('cost')
                    ->money('BDT')
                    ->alignRight(),
                TextColumn::make('previous_balance')
                    ->label('Previous Balance')
                    ->state(fn (Message $record): ?float => $this->resolvePreviousBalance($record))
                    ->money('BDT')
                    ->alignRight()
                    ->placeholder('—'),
                TextColumn::make('next_balance')
                    ->label('Next Balance')
                    ->state(fn (Message $record): ?float => $this->resolveNextBalance($record))
                    ->money('BDT')
                    ->alignRight()
                    ->placeholder('—'),
                TextColumn::make('parts')
                    ->label('SMS Count')
                    ->numeric()
                    ->alignRight(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('failure_reason')
                    ->label('Failure Reason')
                    ->state(fn (Message $record): ?string => $this->resolveFailureReason($record))
                    ->placeholder('—'),
                TextColumn::make('log_date_time')
                    ->label('Date & Time')
                    ->state(fn (Message $record) => $record->delivered_at ?? $record->sent_at ?? $record->failed_at ?? $record->created_at)
                    ->dateTime('d M Y, h:i A')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw(
                            "COALESCE(delivered_at, sent_at, failed_at, created_at) {$direction}",
                        );
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Message $record): string => MessageResource::getUrl('view', ['record' => $record])),
            ])
            ->recordUrl(fn (Model $record): string => MessageResource::getUrl('view', ['record' => $record]))
            ->toolbarActions([])
            ->filters([
                SelectFilter::make('sender_id')
                    ->label('Sender')
                    ->relationship(
                        name: 'sender',
                        titleAttribute: 'sender_id',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->accessible(),
                    ),
                SelectFilter::make('status')
                    ->options(MessageStatus::class),
                Filter::make('created_at')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('Created From'),
                        DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ], layout: FiltersLayout::AboveContent);
    }

    protected function getTableQuery(): Builder|Relation|null
    {
        return Message::query()
            ->whereNotNull('batch_id')
            ->select('messages.*')
            ->with([
                'sender:id,sender_id',
                'messageBatch:id,source_type,source_config,total_recipients',
            ])
            ->addSelect([
                'batch_balance_before' => Transaction::query()
                    ->select('balance_before')
                    ->where('transactionable_type', TransactionableType::MessageBatch->value)
                    ->whereColumn('transactionable_id', 'messages.batch_id')
                    ->latest('id')
                    ->limit(1),
                'batch_balance_after' => Transaction::query()
                    ->select('balance_after')
                    ->where('transactionable_type', TransactionableType::MessageBatch->value)
                    ->whereColumn('transactionable_id', 'messages.batch_id')
                    ->latest('id')
                    ->limit(1),
                'refund_balance_after' => Transaction::query()
                    ->select('balance_after')
                    ->where('transactionable_type', TransactionableType::Message->value)
                    ->where('direction', TransactionDirection::Credit->value)
                    ->whereColumn('transactionable_id', 'messages.id')
                    ->latest('id')
                    ->limit(1),
                'matched_request_payload' => SmsLog::query()
                    ->select('request_payload')
                    ->whereColumn('message_id', 'messages.id')
                    ->whereNotNull('request_payload')
                    ->latest('id')
                    ->limit(1),
                'matched_response_payload' => SmsLog::query()
                    ->select('response_payload')
                    ->whereColumn('message_id', 'messages.id')
                    ->whereNotNull('response_payload')
                    ->latest('id')
                    ->limit(1),
                'matched_log_error_message' => SmsLog::query()
                    ->select('error_message')
                    ->whereColumn('message_id', 'messages.id')
                    ->whereNotNull('error_message')
                    ->latest('id')
                    ->limit(1),
            ]);
    }

    private function resolveSmsType(Message $message): string
    {
        return $this->extractSmsTypeFromPayload($message->matched_request_payload)
            ?? $this->extractSmsTypeFromPayload($message->matched_response_payload)
            ?? $this->fallbackSmsType($message);
    }

    private function resolvePreviousBalance(Message $message): ?float
    {
        return $message->batch_balance_before;
    }

    private function resolveNextBalance(Message $message): ?float
    {
        return $message->refund_balance_after ?? $message->batch_balance_after;
    }

    private function resolveFailureReason(Message $message): ?string
    {
        if ($message->status !== MessageStatus::Failed && $message->status !== MessageStatus::DlrFailed) {
            return null;
        }

        return $message->failed_reason ?: $message->matched_log_error_message;
    }

    private function isQuickMessageFailure(Message $message): bool
    {
        return $message->status === MessageStatus::Failed && $this->isQuickMessage($message);
    }

    private function isQuickMessage(Message $message): bool
    {
        $batch = $message->messageBatch;

        if (! $batch || $batch->source_type !== BatchSource::Manual) {
            return false;
        }

        $recipientCount = (int) $batch->total_recipients;

        if ($recipientCount === 0) {
            $recipientCount = count($batch->source_config['recipients'] ?? []);
        }

        return $recipientCount === 1;
    }

    private function fallbackSmsType(Message $message): string
    {
        $batch = $message->messageBatch;

        if (! $batch) {
            return 'Transactional';
        }

        $recipientCount = (int) $batch->total_recipients;

        if ($recipientCount === 0) {
            $recipientCount = count($batch->source_config['recipients'] ?? []);
        }

        return $recipientCount > 1 ? 'Promotional' : 'Transactional';
    }

    private function extractSmsTypeFromPayload(?string $payload): ?string
    {
        if (blank($payload)) {
            return null;
        }

        $decodedPayload = json_decode($payload, true);

        if (! is_array($decodedPayload)) {
            return null;
        }

        foreach ([
            data_get($decodedPayload, 'transactionType'),
            data_get($decodedPayload, 'transtype'),
            data_get($decodedPayload, 'sms_type'),
            data_get($decodedPayload, 'smsType'),
            data_get($decodedPayload, 'type'),
        ] as $rawType) {
            $resolvedType = $this->normalizeSmsType($rawType);

            if ($resolvedType !== null) {
                return $resolvedType;
            }
        }

        return null;
    }

    private function normalizeSmsType(mixed $rawType): ?string
    {
        if (! is_string($rawType) && ! is_numeric($rawType)) {
            return null;
        }

        $normalizedType = strtoupper(trim((string) $rawType));

        return match ($normalizedType) {
            'T', 'TRANSACTIONAL', 'OTP' => 'Transactional',
            'P', 'PROMOTIONAL' => 'Promotional',
            default => null,
        };
    }
}
