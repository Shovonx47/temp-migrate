<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Enums\SupermanNavigationGroup;
use App\Models\Gateway;
use App\Models\Message;
use App\Models\Sender;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class CustomerReportsPage extends AbstractReportPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Reports;

    protected static ?string $navigationLabel = 'Customer Reports';

    protected static ?string $title = 'Customer Reports';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'reports/customer';

    protected string $view = 'filament.admin.reports.customer-report';

    public ?string $datePreset = 'all';

    public ?string $fromDate = null;

    public ?string $toDate = null;

    protected function reportType(): string
    {
        return 'customer';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset_filters')
                ->label('Reset Filters')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(fn () => $this->resetReportFilters()),
            Action::make('export_xlsx')
                ->label('Export Excel')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn (): BinaryFileResponse => $this->downloadXlsx()),
            Action::make('export_pdf')
                ->label('Export PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->downloadPdf()),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->query())
            ->columns($this->columns())
            ->filters($this->filters())
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns([
                'default' => 1,
                'sm' => 2,
                'md' => 3,
                'lg' => 4,
                'xl' => 7,
            ])
            ->defaultSort('report_date', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25);
    }

    protected function query(): Builder
    {
        $hasTable = $this->isTableInitialized();
        $tableFrom = $hasTable ? ($this->getTableFilterState('from_date')['from'] ?? null) : null;
        $tableUntil = $hasTable ? ($this->getTableFilterState('to_date')['until'] ?? null) : null;
        $from = $tableFrom ?: $this->fromDate;
        $until = $tableUntil ?: $this->toDate;

        $companyName = $hasTable ? ($this->getTableFilterState('company_name')['value'] ?? null) : null;
        $customerId = $hasTable ? ($this->getTableFilterState('customer_id')['value'] ?? null) : null;
        $smsType = $hasTable ? ($this->getTableFilterState('sms_type')['value'] ?? null) : null;
        $smsStatus = $hasTable ? ($this->getTableFilterState('sms_status')['value'] ?? null) : null;
        $gatewayId = $hasTable ? ($this->getTableFilterState('gateway_id')['value'] ?? null) : null;
        $senderId = $hasTable ? ($this->getTableFilterState('sender_id')['value'] ?? null) : null;
        $groupBy = $hasTable ? ($this->getTableFilterState('group_by')['value'] ?? 'daily') : 'daily';

        $msgQuery = DB::table('messages')
            ->join('users', 'messages.user_id', '=', 'users.id')
            ->leftJoin('senders', 'messages.sender_id', '=', 'senders.id')
            ->when($from, fn ($q) => $q->whereDate('messages.created_at', '>=', $from))
            ->when($until, fn ($q) => $q->whereDate('messages.created_at', '<=', $until))
            ->when($companyName, fn ($q) => $q->where('users.business_name', $companyName))
            ->when($customerId, fn ($q) => $q->where('messages.user_id', $customerId))
            ->when($gatewayId, fn ($q) => $q->where('messages.gateway_id', $gatewayId))
            ->when($senderId, fn ($q) => $q->where('messages.sender_id', $senderId))
            ->when($smsType === 'masking', fn ($q) => $q->whereIn('senders.type', ['alphanumeric', 'shortcode']))
            ->when($smsType === 'non_masking', fn ($q) => $q->where(fn ($sub) => $sub->where('senders.type', 'numeric')->orWhereNull('senders.type')))
            ->when($smsStatus === 'delivered', fn ($q) => $q->where('messages.status', 'delivered'))
            ->when($smsStatus === 'failed', fn ($q) => $q->whereIn('messages.status', ['failed', 'dlr_failed']))
            ->when($smsStatus === 'pending', fn ($q) => $q->whereIn('messages.status', ['pending', 'processing', 'queued']));

        if ($groupBy === 'customer') {
            $dateLabel = ($from && $until)
                ? "$from ~ $until"
                : ($from ? "Since $from" : ($until ? "Until $until" : 'All Time'));

            $msgQuery->select([
                DB::raw('MAX(messages.id) as id'),
                DB::raw("'$dateLabel' as report_date"),
                'users.id as user_id',
                'users.business_name',
                'users.name as customer_name',
                'users.email',
                'users.phone',
                'users.balance',
                DB::raw('COUNT(messages.id) as total_sms'),
                DB::raw("SUM(CASE WHEN messages.status = 'delivered' THEN 1 ELSE 0 END) as delivered_count"),
                DB::raw("SUM(CASE WHEN messages.status IN ('failed', 'dlr_failed') THEN 1 ELSE 0 END) as failed_count"),
                DB::raw("SUM(CASE WHEN senders.type IN ('alphanumeric', 'shortcode') THEN 1 ELSE 0 END) as masking_count"),
                DB::raw("SUM(CASE WHEN senders.type = 'numeric' OR senders.type IS NULL THEN 1 ELSE 0 END) as non_masking_count"),
                DB::raw('SUM(messages.cost) as customer_spend'),
                DB::raw('SUM(COALESCE(messages.operator_cost, 0) + COALESCE(messages.primary_dipping_cost, 0)) as provider_cost'),
                DB::raw('(SUM(messages.cost) - SUM(COALESCE(messages.operator_cost, 0) + COALESCE(messages.primary_dipping_cost, 0))) as profit'),
                DB::raw('MAX(messages.created_at) as last_sms_sent'),
            ]);
            $msgQuery->groupBy('users.id', 'users.business_name', 'users.name', 'users.email', 'users.phone', 'users.balance');
        } else {
            $msgQuery->select([
                DB::raw('MAX(messages.id) as id'),
                DB::raw('DATE(messages.created_at) as report_date'),
                'users.id as user_id',
                'users.business_name',
                'users.name as customer_name',
                'users.email',
                'users.phone',
                'users.balance',
                DB::raw('COUNT(messages.id) as total_sms'),
                DB::raw("SUM(CASE WHEN messages.status = 'delivered' THEN 1 ELSE 0 END) as delivered_count"),
                DB::raw("SUM(CASE WHEN messages.status IN ('failed', 'dlr_failed') THEN 1 ELSE 0 END) as failed_count"),
                DB::raw("SUM(CASE WHEN senders.type IN ('alphanumeric', 'shortcode') THEN 1 ELSE 0 END) as masking_count"),
                DB::raw("SUM(CASE WHEN senders.type = 'numeric' OR senders.type IS NULL THEN 1 ELSE 0 END) as non_masking_count"),
                DB::raw('SUM(messages.cost) as customer_spend'),
                DB::raw('SUM(COALESCE(messages.operator_cost, 0) + COALESCE(messages.primary_dipping_cost, 0)) as provider_cost'),
                DB::raw('(SUM(messages.cost) - SUM(COALESCE(messages.operator_cost, 0) + COALESCE(messages.primary_dipping_cost, 0))) as profit'),
                DB::raw('MAX(messages.created_at) as last_sms_sent'),
            ]);
            $msgQuery->groupBy(DB::raw('DATE(messages.created_at)'), 'users.id', 'users.business_name', 'users.name', 'users.email', 'users.phone', 'users.balance');
        }

        $model = (new Message)->setTable('customer_reports');

        return $model->newQuery()->fromSub($msgQuery, 'customer_reports');
    }

    /** @return array<int, TextColumn> */
    protected function columns(): array
    {
        return [
            TextColumn::make('report_date')
                ->label('Date')
                ->sortable()
                ->formatStateUsing(function ($state): string {
                    if (! $state) {
                        return '—';
                    }
                    if (str_contains($state, '~') || str_contains($state, 'Since') || str_contains($state, 'Until') || $state === 'All Time') {
                        return $state;
                    }

                    try {
                        return Carbon::parse($state)->format('d M Y');
                    } catch (\Throwable) {
                        return (string) $state;
                    }
                }),

            TextColumn::make('business_name')
                ->label('Company Name')
                ->weight('bold')
                ->color('primary')
                ->placeholder('—')
                ->searchable()
                ->sortable(),

            TextColumn::make('customer_name')
                ->label('Customer Name')
                ->description(fn ($record): ?string => $record->email ?: null)
                ->searchable()
                ->sortable(),

            TextColumn::make('total_sms')
                ->label('Total SMS')
                ->numeric()
                ->weight('semibold')
                ->alignEnd()
                ->sortable()
                ->summarize(
                    Sum::make()->label('Total')->numeric()
                ),

            TextColumn::make('delivered_count')
                ->label('Delivered')
                ->numeric()
                ->color('success')
                ->alignEnd()
                ->sortable()
                ->summarize(
                    Sum::make()->label('Total')->numeric()
                ),

            TextColumn::make('failed_count')
                ->label('Failed')
                ->numeric()
                ->color(fn ($state): ?string => (int) $state > 0 ? 'danger' : null)
                ->alignEnd()
                ->sortable()
                ->summarize(
                    Sum::make()->label('Total')->numeric()
                ),

            TextColumn::make('masking_count')
                ->label('Masking SMS')
                ->numeric()
                ->alignEnd()
                ->sortable()
                ->summarize(
                    Sum::make()->label('Total')->numeric()
                ),

            TextColumn::make('non_masking_count')
                ->label('Non-Masking SMS')
                ->numeric()
                ->alignEnd()
                ->sortable()
                ->summarize(
                    Sum::make()->label('Total')->numeric()
                ),

            TextColumn::make('customer_spend')
                ->label('Customer Spend')
                ->money('BDT')
                ->weight('medium')
                ->alignEnd()
                ->sortable()
                ->summarize(
                    Sum::make()->label('Total')->money('BDT')
                ),

            TextColumn::make('provider_cost')
                ->label('Provider Cost')
                ->money('BDT')
                ->alignEnd()
                ->sortable()
                ->summarize(
                    Sum::make()->label('Total')->money('BDT')
                ),

            TextColumn::make('profit')
                ->label('Profit')
                ->money('BDT')
                ->weight('bold')
                ->color(fn ($state): ?string => (float) $state >= 0 ? 'success' : 'danger')
                ->alignEnd()
                ->sortable()
                ->summarize(
                    Sum::make()->label('Total')->money('BDT')
                ),

            TextColumn::make('balance')
                ->label('Balance')
                ->money('BDT')
                ->alignEnd()
                ->sortable(),

            TextColumn::make('last_sms_sent')
                ->label('Last SMS Sent')
                ->dateTime('d M Y, h:i A')
                ->placeholder('—')
                ->alignEnd()
                ->sortable(),
        ];
    }

    /** @return array<int, Filter|SelectFilter> */
    protected function filters(): array
    {
        return [
            SelectFilter::make('company_name')
                ->label('Company Name')
                ->placeholder('All Companies')
                ->options(fn () => User::customers()->whereNotNull('business_name')->where('business_name', '!=', '')->pluck('business_name', 'business_name')->toArray())
                ->searchable()
                ->preload()
                ->query(fn (Builder $query) => $query),

            SelectFilter::make('customer_id')
                ->label('Customer Name')
                ->placeholder('All Customers')
                ->options(fn () => User::customers()->pluck('name', 'id')->toArray())
                ->searchable()
                ->preload()
                ->query(fn (Builder $query) => $query),

            SelectFilter::make('sms_type')
                ->label('SMS Type')
                ->placeholder('All Types')
                ->options([
                    'masking' => 'Masking',
                    'non_masking' => 'Non-Masking',
                ])
                ->query(fn (Builder $query) => $query),

            SelectFilter::make('sms_status')
                ->label('SMS Status')
                ->placeholder('All Statuses')
                ->options([
                    'delivered' => 'Delivered',
                    'failed' => 'Failed',
                    'pending' => 'Pending',
                ])
                ->query(fn (Builder $query) => $query),

            SelectFilter::make('gateway_id')
                ->label('Provider')
                ->placeholder('All Providers')
                ->options(fn () => Gateway::pluck('name', 'id')->toArray())
                ->searchable()
                ->preload()
                ->query(fn (Builder $query) => $query),

            SelectFilter::make('sender_id')
                ->label('Sender ID')
                ->placeholder('All Sender IDs')
                ->options(fn () => Sender::withoutGlobalScopes()->pluck('sender_id', 'id')->toArray())
                ->searchable()
                ->preload()
                ->query(fn (Builder $query) => $query),

            SelectFilter::make('group_by')
                ->label('Grouping')
                ->options([
                    'daily' => 'Daily (Date & Customer)',
                    'customer' => 'Customer Summary',
                ])
                ->default('daily')
                ->query(fn (Builder $query) => $query),

            Filter::make('from_date')
                ->schema([
                    DatePicker::make('from')
                        ->label('From Date')
                        ->placeholder('Select start date')
                        ->native(false)
                        ->closeOnDateSelection(),
                ])
                ->query(fn (Builder $query): Builder => $query),

            Filter::make('to_date')
                ->schema([
                    DatePicker::make('until')
                        ->label('To Date')
                        ->placeholder('Select end date')
                        ->native(false)
                        ->closeOnDateSelection(),
                ])
                ->query(fn (Builder $query): Builder => $query),
        ];
    }

    protected function isTableInitialized(): bool
    {
        return (new \ReflectionProperty($this, 'table'))->isInitialized($this);
    }

    public function setDatePreset(string $preset): void
    {
        $this->datePreset = $preset;
        $now = now();

        switch ($preset) {
            case 'today':
                $this->fromDate = $now->toDateString();
                $this->toDate = $now->toDateString();
                break;
            case 'yesterday':
                $this->fromDate = $now->copy()->subDay()->toDateString();
                $this->toDate = $now->copy()->subDay()->toDateString();
                break;
            case 'this_week':
                $this->fromDate = $now->copy()->startOfWeek()->toDateString();
                $this->toDate = $now->copy()->endOfWeek()->toDateString();
                break;
            case 'last_week':
                $this->fromDate = $now->copy()->subWeek()->startOfWeek()->toDateString();
                $this->toDate = $now->copy()->subWeek()->endOfWeek()->toDateString();
                break;
            case 'this_month':
                $this->fromDate = $now->copy()->startOfMonth()->toDateString();
                $this->toDate = $now->copy()->endOfMonth()->toDateString();
                break;
            case 'last_month':
                $this->fromDate = $now->copy()->subMonth()->startOfMonth()->toDateString();
                $this->toDate = $now->copy()->subMonth()->endOfMonth()->toDateString();
                break;
            case 'this_year':
                $this->fromDate = $now->copy()->startOfYear()->toDateString();
                $this->toDate = $now->copy()->endOfYear()->toDateString();
                break;
            case 'all':
            default:
                $this->fromDate = null;
                $this->toDate = null;
                break;
        }

        if ($this->isTableInitialized()) {
            if (isset($this->tableFilters['from_date'])) {
                $this->tableFilters['from_date']['from'] = $this->fromDate;
            }
            if (isset($this->tableFilters['to_date'])) {
                $this->tableFilters['to_date']['until'] = $this->toDate;
            }
            $this->resetPage();
        }
    }

    public function updatedFromDate(): void
    {
        $this->datePreset = 'custom';
        if ($this->isTableInitialized()) {
            if (isset($this->tableFilters['from_date'])) {
                $this->tableFilters['from_date']['from'] = $this->fromDate;
            }
            $this->resetPage();
        }
    }

    public function updatedToDate(): void
    {
        $this->datePreset = 'custom';
        if ($this->isTableInitialized()) {
            if (isset($this->tableFilters['to_date'])) {
                $this->tableFilters['to_date']['until'] = $this->toDate;
            }
            $this->resetPage();
        }
    }

    public function resetReportFilters(): void
    {
        $this->datePreset = 'all';
        $this->fromDate = null;
        $this->toDate = null;
        if ($this->isTableInitialized()) {
            $this->resetTableFilters();
            $this->resetTableSearch();
            $this->resetPage();
        }
    }

    public function getTimeframeLabel(): string
    {
        $tableFrom = $this->isTableInitialized() ? ($this->getTableFilterState('from_date')['from'] ?? null) : null;
        $tableUntil = $this->isTableInitialized() ? ($this->getTableFilterState('to_date')['until'] ?? null) : null;
        $from = $tableFrom ?: $this->fromDate;
        $until = $tableUntil ?: $this->toDate;

        if ($from && $until) {
            if ($from === $until) {
                return 'Showing data for '.Carbon::parse($from)->toFormattedDateString();
            }

            return 'Showing data from '.Carbon::parse($from)->toFormattedDateString().' to '.Carbon::parse($until)->toFormattedDateString();
        }

        if ($from) {
            return 'Showing data since '.Carbon::parse($from)->toFormattedDateString();
        }

        if ($until) {
            return 'Showing data up to '.Carbon::parse($until)->toFormattedDateString();
        }

        return 'Showing all-time cumulative data';
    }

    /**
     * @return array{
     *     total_companies: int,
     *     total_sms: int,
     *     delivered_count: int,
     *     failed_count: int,
     *     masking_count: int,
     *     non_masking_count: int,
     *     delivery_rate: float,
     *     failure_rate: float,
     *     customer_spend: float,
     *     provider_cost: float,
     *     total_profit: float,
     *     margin_rate: float,
     *     total_balance: float
     * }
     */
    public function getSummaryData(): array
    {
        $query = $this->isTableInitialized() ? $this->getFilteredTableQuery() : $this->query();
        $baseQuery = (clone $query)->reorder()->toBase();

        $totals = DB::connection()->table($baseQuery, 'sub')
            ->selectRaw("
                COUNT(DISTINCT user_id) as total_companies,
                COALESCE(SUM(total_sms), 0) as total_sms,
                COALESCE(SUM(delivered_count), 0) as delivered_count,
                COALESCE(SUM(failed_count), 0) as failed_count,
                COALESCE(SUM(masking_count), 0) as masking_count,
                COALESCE(SUM(non_masking_count), 0) as non_masking_count,
                COALESCE(SUM(customer_spend), 0) as customer_spend,
                COALESCE(SUM(provider_cost), 0) as provider_cost,
                COALESCE(SUM(profit), 0) as total_profit
            ")
            ->first();

        $totalBalance = (float) DB::connection()->table($baseQuery, 'sub')
            ->selectRaw('user_id, MAX(balance) as balance')
            ->groupBy('user_id')
            ->get()
            ->sum('balance');

        $totalSms = (int) ($totals->total_sms ?? 0);
        $deliveredCount = (int) ($totals->delivered_count ?? 0);
        $failedCount = (int) ($totals->failed_count ?? 0);
        $customerSpend = (float) ($totals->customer_spend ?? 0);
        $providerCost = (float) ($totals->provider_cost ?? 0);
        $totalProfit = (float) ($totals->total_profit ?? 0);

        return [
            'total_companies' => (int) ($totals->total_companies ?? 0),
            'total_sms' => $totalSms,
            'delivered_count' => $deliveredCount,
            'failed_count' => $failedCount,
            'masking_count' => (int) ($totals->masking_count ?? 0),
            'non_masking_count' => (int) ($totals->non_masking_count ?? 0),
            'delivery_rate' => $totalSms > 0 ? round(($deliveredCount / $totalSms) * 100, 1) : 0.0,
            'failure_rate' => $totalSms > 0 ? round(($failedCount / $totalSms) * 100, 1) : 0.0,
            'customer_spend' => $customerSpend,
            'provider_cost' => $providerCost,
            'total_profit' => $totalProfit,
            'margin_rate' => $customerSpend > 0 ? round(($totalProfit / $customerSpend) * 100, 1) : 0.0,
            'total_balance' => $totalBalance,
        ];
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            'Date',
            'Company Name',
            'Customer Name',
            'Total SMS',
            'Delivered',
            'Failed',
            'Masking SMS',
            'Non-Masking SMS',
            'Customer Spend (BDT)',
            'Provider Cost (BDT)',
            'Profit (BDT)',
            'Balance (BDT)',
            'Last SMS Sent',
        ];
    }

    public function reportRows(): Collection
    {
        $query = $this->isTableInitialized() ? $this->getFilteredTableQuery() : $this->query();
        $rows = $query->get()->map(fn ($record): array => [
            $record->report_date,
            $record->business_name ?: '—',
            $record->customer_name.($record->email ? " ({$record->email})" : ''),
            (int) ($record->total_sms ?? 0),
            (int) ($record->delivered_count ?? 0),
            (int) ($record->failed_count ?? 0),
            (int) ($record->masking_count ?? 0),
            (int) ($record->non_masking_count ?? 0),
            number_format((float) ($record->customer_spend ?? 0), 2, '.', ''),
            number_format((float) ($record->provider_cost ?? 0), 2, '.', ''),
            number_format((float) ($record->profit ?? 0), 2, '.', ''),
            number_format((float) ($record->balance ?? 0), 2, '.', ''),
            $record->last_sms_sent ? Carbon::parse($record->last_sms_sent)->format('Y-m-d H:i:s') : '—',
        ]);

        if ($rows->isNotEmpty()) {
            $summary = $this->getSummaryData();
            $rows->push([
                'TOTAL',
                $summary['total_companies'].' Companies',
                '—',
                $summary['total_sms'],
                $summary['delivered_count'],
                $summary['failed_count'],
                $summary['masking_count'],
                $summary['non_masking_count'],
                number_format($summary['customer_spend'], 2, '.', ''),
                number_format($summary['provider_cost'], 2, '.', ''),
                number_format($summary['total_profit'], 2, '.', ''),
                number_format($summary['total_balance'], 2, '.', ''),
                '—',
            ]);
        }

        return $rows;
    }
}
