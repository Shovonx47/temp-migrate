<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Enums\GatewayDriver;
use App\Enums\MessageSource;
use App\Enums\MessageStatus;
use App\Enums\SupermanNavigationGroup;
use App\Enums\TransactionableType;
use App\Enums\TransactionDirection;
use App\Models\Message;
use App\Models\Transaction;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

class SmsReportsPage extends AbstractReportPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Reports;

    protected static ?string $navigationLabel = 'SMS Reports';

    protected static ?string $title = 'SMS Reports';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'reports/sms';

    protected function reportType(): string
    {
        return 'sms';
    }

    protected function query(): Builder
    {
        return Message::withoutGlobalScopes()
            ->select('messages.*')
            ->with([
                'gateway:id,name,driver',
                'sender:id,sender_id',
                'user:id,name',
            ])
            ->addSelect([
                'batch_balance_before' => Transaction::query()
                    ->select('balance_before')
                    ->where('transactionable_type', TransactionableType::MessageBatch->value)
                    ->where('direction', TransactionDirection::Debit->value)
                    ->whereColumn('transactionable_id', 'messages.batch_id')
                    ->latest('id')
                    ->limit(1),
                'batch_balance_after' => Transaction::query()
                    ->select('balance_after')
                    ->where('transactionable_type', TransactionableType::MessageBatch->value)
                    ->where('direction', TransactionDirection::Debit->value)
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
            ]);
    }

    /** @return array<int, TextColumn> */
    protected function columns(): array
    {
        return [
            TextColumn::make('to')->label('Recipient Number')->searchable()->copyable(),
            TextColumn::make('body')->label('Content')->limit(60)->tooltip(fn (Message $record): string => $record->body)->searchable(),
            TextColumn::make('sender.sender_id')->label('Sender ID')->badge()->searchable()->placeholder('—'),
            TextColumn::make('bill_msisdn')->label('Bill MSISDN')->copyable()->placeholder('—'),
            TextColumn::make('sms_type')->label('SMS Type')->badge()->placeholder('—'),
            TextColumn::make('parts')->label('SMS Count')->numeric()->alignRight()->sortable(),
            TextColumn::make('cost')->label('Cost')->money('BDT')->alignRight()->sortable(),
            TextColumn::make('batch_balance_before')->label('Previous Balance')->money('BDT')->alignRight()->placeholder('—'),
            TextColumn::make('next_balance')
                ->label('Next Balance')
                ->state(fn (Message $record): ?float => $record->refund_balance_after ?? $record->batch_balance_after)
                ->money('BDT')
                ->alignRight()
                ->placeholder('—'),
            TextColumn::make('status')->badge()->sortable(),
            TextColumn::make('failed_reason')->label('Failure Reason')->limit(60)->tooltip(fn (Message $record): ?string => $record->failed_reason)->placeholder('—'),
            TextColumn::make('operator_name')->label('Operator')->placeholder('—'),
            TextColumn::make('channel')->label('Channel')->badge()->placeholder('—'),
            TextColumn::make('submit_time')
                ->label('Submit Time')
                ->state(fn (Message $record) => $record->sent_at ?? $record->created_at)
                ->dateTime()
                ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw("COALESCE(sent_at, created_at) {$direction}")),
            TextColumn::make('delivered_at')->label('Delivery Time')->dateTime()->placeholder('—')->sortable(),
            TextColumn::make('tariff_name')->label('Tariff')->placeholder('—'),
            TextColumn::make('api_user')
                ->label('API User')
                ->state(fn (Message $record): ?string => $record->source === MessageSource::API ? $record->user?->name : null)
                ->placeholder('—'),
            TextColumn::make('source_ip')->label('Source IP')->placeholder('—'),
            TextColumn::make('request_id')->label('Request ID')->copyable()->placeholder('—'),
            TextColumn::make('server_txn_id')
                ->label('Server TxnId')
                ->state(fn (Message $record): ?string => $record->server_txn_id ?? ($record->gateway?->driver === GatewayDriver::Infozillion ? $record->gateway_message_id : null))
                ->copyable()
                ->placeholder('—'),
            TextColumn::make('server_response_code')->label('ServerResponseCode')->placeholder('—'),
            TextColumn::make('mno_txn_id')->label('MNO TxnId')->copyable()->placeholder('—'),
            TextColumn::make('mno_response_code')->label('MNO Response Code')->placeholder('—'),
            TextColumn::make('mno_response_message')->label('MNO Response Message')->limit(60)->tooltip(fn (Message $record): ?string => $record->mno_response_message)->placeholder('—'),
        ];
    }

    /** @return array<int, Filter|SelectFilter> */
    protected function filters(): array
    {
        return [
            SelectFilter::make('status')->options(MessageStatus::class),
            SelectFilter::make('source')->label('Source')->options(MessageSource::class),
            SelectFilter::make('user_id')->label('Customer')->relationship('user', 'name')->searchable(),
            SelectFilter::make('gateway_id')->label('Provider')->relationship('gateway', 'name')->searchable(),
            Filter::make('created_at')
                ->schema([DatePicker::make('from'), DatePicker::make('until')])
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
        ];
    }

    protected function reportRows(): Collection
    {
        return $this->getFilteredTableQuery()->limit(1000)->get()->map(fn (Message $record): array => [
            $record->to,
            $record->body,
            $record->sender?->sender_id,
            $record->bill_msisdn,
            $record->sms_type,
            $record->parts,
            $record->cost,
            $record->batch_balance_before,
            $record->refund_balance_after ?? $record->batch_balance_after,
            $record->status?->value ?? $record->status,
            $record->failed_reason,
            $record->operator_name,
            $record->channel,
            ($record->sent_at ?? $record->created_at)?->format('Y-m-d H:i:s'),
            $record->delivered_at?->format('Y-m-d H:i:s'),
            $record->tariff_name,
            $record->source === MessageSource::API ? $record->user?->name : null,
            $record->source_ip,
            $record->request_id,
            $record->server_txn_id ?? ($record->gateway?->driver === GatewayDriver::Infozillion ? $record->gateway_message_id : null),
            $record->server_response_code,
            $record->mno_txn_id,
            $record->mno_response_code,
            $record->mno_response_message,
        ]);
    }

    /** @return array<int, string> */
    protected function headings(): array
    {
        return [
            'Recipient Number', 'Content', 'Sender ID', 'Bill MSISDN', 'SMS Type', 'SMS Count', 'Cost', 'Previous Balance', 'Next Balance', 'Status', 'Failure Reason', 'Operator', 'Channel', 'Submit Time', 'Delivery Time', 'Tariff', 'API User', 'Source IP', 'Request ID', 'Server TxnId', 'ServerResponseCode', 'MNO TxnId', 'MNO Response Code', 'MNO Response Message',
        ];
    }
}
