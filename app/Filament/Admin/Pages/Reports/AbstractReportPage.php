<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Enums\MessageStatus;
use App\Enums\SenderType;
use App\Enums\TransactionDirection;
use App\Exports\TabularReportExport;
use App\Models\Gateway;
use App\Models\Message;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReportExportService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class AbstractReportPage extends Page implements HasTable
{
    use InteractsWithTable;

    abstract protected function reportType(): string;

    protected string $view = 'filament.admin.reports.table-report';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_xlsx')->label('Export Excel')->icon('heroicon-o-arrow-down-tray')->action(fn (): BinaryFileResponse => $this->downloadXlsx()),
            Action::make('export_pdf')->label('Export PDF')->icon('heroicon-o-document-arrow-down')->color('gray')->action(fn (): StreamedResponse => $this->downloadPdf()),
        ];
    }

    public function table(Table $table): Table
    {
        return $table->query($this->query())->columns($this->columns())->filters($this->filters())->defaultSort($this->defaultSortColumn(), 'desc');
    }

    protected function query(): Builder
    {
        return match ($this->reportType()) {
            'sms', 'executive' => Message::withoutGlobalScopes()->with(['user:id,name', 'sender:id,sender_id,type', 'gateway:id,name']),
            'masking' => Message::withoutGlobalScopes()->with(['user:id,name', 'sender:id,sender_id,type', 'gateway:id,name'])->whereHas('sender', fn (Builder $query) => $query->whereIn('type', [SenderType::AlphaNumeric->value, SenderType::Shortcode->value])),
            'non_masking' => Message::withoutGlobalScopes()->with(['user:id,name', 'sender:id,sender_id,type', 'gateway:id,name'])->whereHas('sender', fn (Builder $query) => $query->where('type', SenderType::Numeric->value)),
            'customer' => User::withoutGlobalScopes()->customers()->withCount('messages')->withSum('messages', 'cost'),
            'provider' => Gateway::withoutGlobalScopes()->withCount('messages')->withSum('messages', 'cost'),
            'financial' => Transaction::withoutGlobalScopes()->with('user:id,name'),
        };
    }

    /** @return array<int, TextColumn> */
    protected function columns(): array
    {
        return match ($this->reportType()) {
            'customer' => [TextColumn::make('name')->label('Customer')->searchable()->sortable(), TextColumn::make('email')->searchable(), TextColumn::make('messages_count')->label('SMS')->numeric()->sortable(), TextColumn::make('messages_sum_cost')->label('SMS Spend')->money('BDT')->sortable(), TextColumn::make('balance')->money('BDT')->sortable(), TextColumn::make('created_at')->label('Joined')->date()->sortable()],
            'provider' => [TextColumn::make('name')->label('Provider / Gateway')->searchable()->sortable(), TextColumn::make('driver')->badge(), TextColumn::make('messages_count')->label('SMS')->numeric()->sortable(), TextColumn::make('messages_sum_cost')->label('Revenue')->money('BDT')->sortable(), TextColumn::make('health_status')->label('Health')->badge()->placeholder('Unknown'), TextColumn::make('is_enabled')->label('Enabled')->badge()->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')],
            'financial' => [TextColumn::make('user.name')->label('Customer')->searchable()->sortable(), TextColumn::make('direction')->badge()->sortable(), TextColumn::make('amount')->money('BDT')->sortable(), TextColumn::make('balance_before')->money('BDT')->placeholder('—'), TextColumn::make('balance_after')->money('BDT')->placeholder('—'), TextColumn::make('description')->limit(60), TextColumn::make('created_at')->dateTime()->sortable()],
            default => [TextColumn::make('user.name')->label('Customer')->searchable()->sortable(), TextColumn::make('to')->label('Recipient')->searchable(), TextColumn::make('sender.sender_id')->label('Sender ID')->searchable()->badge(), TextColumn::make('gateway.name')->label('Provider')->searchable(), TextColumn::make('parts')->label('SMS Parts')->numeric()->sortable(), TextColumn::make('cost')->label('Charged')->money('BDT')->sortable(), TextColumn::make('status')->badge()->sortable(), TextColumn::make('failed_reason')->label('Failure Reason')->limit(45)->placeholder('—'), TextColumn::make('created_at')->label('Submitted')->dateTime()->sortable()],
        };
    }

    /** @return array<int, Filter|SelectFilter> */
    protected function filters(): array
    {
        $dateFilter = Filter::make('created_at')->schema([DatePicker::make('from'), DatePicker::make('until')])->query(fn (Builder $query, array $data): Builder => $query->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date)));

        return match ($this->reportType()) {
            'sms', 'masking', 'non_masking', 'executive' => [SelectFilter::make('status')->options(MessageStatus::class), SelectFilter::make('user_id')->label('Customer')->relationship('user', 'name')->searchable(), SelectFilter::make('gateway_id')->label('Provider')->relationship('gateway', 'name')->searchable(), $dateFilter],
            'financial' => [SelectFilter::make('direction')->options(TransactionDirection::class), $dateFilter],
            default => [$dateFilter],
        };
    }

    protected function defaultSortColumn(): string
    {
        return in_array($this->reportType(), ['customer', 'provider'], true) ? 'id' : 'created_at';
    }

    protected function reportRows(): Collection
    {
        return $this->getFilteredTableQuery()->limit(1000)->get()->map(fn ($record): array => match ($this->reportType()) {
            'customer' => [$record->name, $record->email, $record->messages_count, $record->messages_sum_cost, $record->balance, $record->created_at?->format('Y-m-d')],
            'provider' => [$record->name, $record->driver?->value ?? $record->driver, $record->messages_count, $record->messages_sum_cost, $record->health_status, $record->is_enabled ? 'Yes' : 'No'],
            'financial' => [$record->user?->name, $record->direction?->value ?? $record->direction, $record->amount, $record->balance_before, $record->balance_after, $record->description, $record->created_at?->format('Y-m-d H:i:s')],
            default => [$record->user?->name, $record->to, $record->sender?->sender_id, $record->gateway?->name, $record->parts, $record->cost, $record->status?->value ?? $record->status, $record->failed_reason, $record->created_at?->format('Y-m-d H:i:s')],
        });
    }

    /** @return array<int, string> */
    protected function headings(): array
    {
        return match ($this->reportType()) {
            'customer' => ['Customer', 'Email', 'SMS', 'SMS Spend', 'Balance', 'Joined'],
            'provider' => ['Provider', 'Driver', 'SMS', 'Revenue', 'Health', 'Enabled'],
            'financial' => ['Customer', 'Direction', 'Amount', 'Balance Before', 'Balance After', 'Description', 'Date'],
            default => ['Customer', 'Recipient', 'Sender ID', 'Provider', 'Parts', 'Charged', 'Status', 'Failure Reason', 'Submitted'],
        };
    }

    protected function downloadXlsx(): BinaryFileResponse
    {
        return Excel::download(new TabularReportExport($this->headings(), $this->reportRows()), $this->reportFilename('xlsx'));
    }

    protected function downloadPdf(): StreamedResponse
    {
        return ReportExportService::downloadPdf($this->reportFilename('pdf'), 'reports.pdf.tabular-report', ['title' => static::$title, 'headings' => $this->headings(), 'rows' => $this->reportRows()]);
    }

    protected function reportFilename(string $extension): string
    {
        return str($this->reportType())->replace('_', '-').'-report-'.now()->format('Y-m-d-His').'.'.$extension;
    }
}
