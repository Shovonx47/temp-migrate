<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\Pages;

use App\Enums\BatchSource;
use App\Enums\MessageStatus;
use App\Enums\TransactionableType;
use App\Enums\TransactionDirection;
use App\Filament\Customer\Resources\Messaging\Messages\MessageResource;
use App\Models\Message;
use App\Models\SmsLog;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

use App\Services\ReportExportService;
use Filament\Actions\ActionGroup;

class ListMessages extends ListRecords
{
    protected static string $resource = MessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createQuick')
                ->label('Quick Message')
                ->icon(Heroicon::OutlinedBolt)
                ->color('gray')
                ->url(fn (): string => MessageResource::getUrl('create-quick')),
            CreateAction::make()
                ->label('Bulk Message')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('gray')
                ->url(MessageResource::getUrl('create-smart')),
            Action::make('exportAs')
                ->label('Export As')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->modalHeading('Export Sent Messages')
                ->modalDescription('Select your preferred report format to download.')
                ->modalSubmitActionLabel('Download Report')
                ->schema([
                    \Filament\Forms\Components\Radio::make('format')
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
            'Message ID',
            'Recipient Number',
            'Sender ID',
            'Content',
            'SMS Type',
            'SMS Count',
            'Cost (BDT)',
            'Previous Balance',
            'Next Balance',
            'Status',
            'Failure Reason',
            'Operator',
            'Channel',
            'Submit Time',
            'Delivery Time',
        ];

        $rows = $query->lazy(500)->map(function ($msg) {
            return [
                'MSG-'.str_pad((string) $msg->id, 6, '0', STR_PAD_LEFT),
                $msg->to,
                $msg->sender->sender_id ?? '',
                $msg->body,
                $this->resolveSmsType($msg),
                $msg->parts ?? 1,
                number_format($msg->cost ?? 0, 4),
                $this->resolvePreviousBalance($msg) !== null ? number_format($this->resolvePreviousBalance($msg), 2) : '',
                $this->resolveNextBalance($msg) !== null ? number_format($this->resolveNextBalance($msg), 2) : '',
                $msg->status->value ?? $msg->status,
                $this->resolveFailureReason($msg) ?? '',
                $this->resolveOperator($msg) ?? '',
                $this->resolveChannel($msg) ?? '',
                ($msg->sent_at ?? $msg->created_at)?->format('Y-m-d H:i:s'),
                $msg->delivered_at?->format('Y-m-d H:i:s'),
            ];
        });

        $filename = 'sent-messages-'.now()->format('Y-m-d-His').'.csv';

        return ReportExportService::downloadCsv($filename, $headers, $rows);
    }

    public function exportPdf()
    {
        $user = auth()->user();
        $records = $this->getFilteredTableQuery()->latest('id')->limit(500)->get();

        $data = [
            'title' => 'Sent Messages Details Report',
            'user' => $user,
            'records' => $records,
            'period' => 'Generated '.now()->format('d M Y, h:i A'),
        ];

        $filename = 'sent-messages-'.now()->format('Y-m-d-His').'.pdf';

        return ReportExportService::downloadPdf($filename, 'reports.pdf.messages-report', $data);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Sent Message Details')
            ->defaultSort('id', 'desc')
            ->poll('30s')
            ->groups([
                Group::make('status')->collapsible(),
            ])
            ->collapsedGroupsByDefault()
            ->columns([
                TextColumn::make('message_id_formatted')
                    ->label('Message ID')
                    ->state(fn (Message $record): string => 'MSG-'.str_pad((string) $record->id, 6, '0', STR_PAD_LEFT))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('id', '=', (int) ltrim(preg_replace('/\D/', '', $search) ?: '0', '0'))),
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
                TextColumn::make('parts')
                    ->label('SMS Count')
                    ->numeric()
                    ->alignRight(),
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
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('failure_reason')
                    ->label('Failure Reason')
                    ->state(fn (Message $record): ?string => $this->resolveFailureReason($record))
                    ->placeholder('—'),
                TextColumn::make('operator')
                    ->label('Operator')
                    ->state(fn (Message $record): ?string => $this->resolveOperator($record))
                    ->placeholder('—'),
                TextColumn::make('channel')
                    ->label('Channel')
                    ->state(fn (Message $record): ?string => $this->resolveChannel($record))
                    ->placeholder('—'),
                TextColumn::make('submit_time')
                    ->label('Submit Time')
                    ->state(fn (Message $record) => $record->sent_at ?? $record->created_at)
                    ->time('h:i A')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw("COALESCE(sent_at, created_at) {$direction}")),
                TextColumn::make('delivery_time')
                    ->label('Delivery Time')
                    ->state(fn (Message $record) => $record->delivered_at)
                    ->time('h:i:s A')
                    ->placeholder('—'),
                TextColumn::make('tariff')
                    ->label('Tariff')
                    ->state(fn (Message $record): ?float => $this->resolveTariff($record))
                    ->money('BDT')
                    ->alignRight()
                    ->placeholder('—'),
                TextColumn::make('api_user')
                    ->label('API User')
                    ->state(fn (Message $record): ?string => $this->resolveApiUser($record))
                    ->placeholder('—'),
                TextColumn::make('source_ip')
                    ->label('Source IP')
                    ->state(fn (Message $record): ?string => $this->resolveSourceIp($record))
                    ->placeholder('—'),
                TextColumn::make('request_id')
                    ->label('Request ID')
                    ->state(fn (Message $record): ?string => $this->resolveRequestId($record))
                    ->placeholder('—'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
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
            ->select('messages.*')
            ->with([
                'sender:id,sender_id',
                'tariffRate:id,masking_price,non_masking_price',
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
                'matched_sms_log_operator' => SmsLog::query()
                    ->select('operator_name')
                    ->whereColumn('message_id', 'messages.id')
                    ->whereNotNull('operator_name')
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
                'matched_gateway_message_id' => SmsLog::query()
                    ->select('gateway_message_id')
                    ->whereColumn('message_id', 'messages.id')
                    ->whereNotNull('gateway_message_id')
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

    private function resolveOperator(Message $message): ?string
    {
        if (filled($message->matched_sms_log_operator)) {
            return $message->matched_sms_log_operator;
        }

        $to = ltrim((string) $message->to, '+');

        return match (true) {
            str_starts_with($to, '88017') || str_starts_with($to, '017') => 'Grameenphone',
            str_starts_with($to, '88018') || str_starts_with($to, '018') => 'Robi',
            str_starts_with($to, '88016') || str_starts_with($to, '016') => 'Airtel',
            str_starts_with($to, '88015') || str_starts_with($to, '015') => 'Teletalk',
            str_starts_with($to, '88014') || str_starts_with($to, '014') => 'Banglalink',
            str_starts_with($to, '88013') || str_starts_with($to, '013') => 'Grameenphone',
            str_starts_with($to, '88019') || str_starts_with($to, '019') => 'Banglalink',
            default => null,
        };
    }

    private function resolveChannel(Message $message): ?string
    {
        $billMsisdn = $this->extractPayloadValue($message->matched_request_payload, ['billMsisdn', 'bill_msisdn']);

        if (blank($billMsisdn)) {
            $operator = $this->resolveOperator($message);

            return match ($operator) {
                'Grameenphone' => 'CH-01',
                'Teletalk' => 'CH-03',
                'Robi', 'Banglalink', 'Airtel' => 'CH-02',
                default => null,
            };
        }

        $normalized = ltrim((string) $billMsisdn, '+88');

        return match (true) {
            str_starts_with($normalized, '017') || str_starts_with($normalized, '17') => 'CH-01',
            str_starts_with($normalized, '015') || str_starts_with($normalized, '15') => 'CH-03',
            str_starts_with($normalized, '018') || str_starts_with($normalized, '18')
            || str_starts_with($normalized, '014') || str_starts_with($normalized, '14')
            || str_starts_with($normalized, '016') || str_starts_with($normalized, '16') => 'CH-02',
            default => 'CH-01',
        };
    }

    private function resolveTariff(Message $message): ?float
    {
        $tariffRate = $message->tariffRate;

        if ($tariffRate !== null) {
            $sender = $message->sender;
            $isMasking = $sender !== null && ! ctype_digit((string) $sender->sender_id);

            return $isMasking
                ? ($tariffRate->masking_price ?? $tariffRate->non_masking_price ?? $message->rate)
                : ($tariffRate->non_masking_price ?? $tariffRate->masking_price ?? $message->rate);
        }

        return $message->rate > 0 ? $message->rate : null;
    }

    private function resolveApiUser(Message $message): ?string
    {
        $username = $this->extractPayloadValue($message->matched_request_payload, ['username']);

        if (blank($username)) {
            return null;
        }

        $suffix = '_API';

        if (! str_ends_with((string) $username, $suffix)) {
            return $username.$suffix;
        }

        return $username;
    }

    private function resolveSourceIp(Message $message): ?string
    {
        $fromResponse = $this->extractPayloadValue($message->matched_response_payload, [
            'sourceIp', 'source_ip', 'serverIp', 'server_ip', 'sourceIP', 'clientIp', 'client_ip',
        ]);

        if (filled($fromResponse)) {
            return $fromResponse;
        }

        $fromRequest = $this->extractPayloadValue($message->matched_request_payload, [
            'sourceIp', 'source_ip', 'sourceIP', 'clientIp', 'client_ip', 'ip', 'ipAddress',
        ]);

        if (filled($fromRequest)) {
            return $fromRequest;
        }

        return null;
    }

    private function resolveRequestId(Message $message): ?string
    {
        $gatewayMsgId = $message->matched_gateway_message_id ?? $message->gateway_message_id;

        if (filled($gatewayMsgId)) {
            $numeric = preg_replace('/\D/', '', (string) $gatewayMsgId);

            if (filled($numeric)) {
                return 'REQ-'.substr($numeric, -6);
            }

            return 'REQ-'.$gatewayMsgId;
        }

        $serverTxnId = $this->extractPayloadValue($message->matched_response_payload, [
            'serverTxnId', 'server_txn_id', 'txnId', 'txn_id', 'transactionId', 'requestId', 'request_id',
        ]);

        if (blank($serverTxnId)) {
            $serverTxnId = $this->extractPayloadValue($message->matched_request_payload, [
                'requestId', 'request_id', 'serverTxnId', 'txnId',
            ]);
        }

        if (filled($serverTxnId)) {
            $numeric = preg_replace('/\D/', '', (string) $serverTxnId);

            if (filled($numeric)) {
                return 'REQ-'.substr($numeric, -6);
            }

            return 'REQ-'.$serverTxnId;
        }

        return null;
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

    /**
     * @param  array<int, string>  $keys
     */
    private function extractPayloadValue(?string $payload, array $keys): mixed
    {
        if (blank($payload)) {
            return null;
        }

        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            return null;
        }

        foreach ($keys as $key) {
            $value = data_get($decoded, $key);

            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }
}
