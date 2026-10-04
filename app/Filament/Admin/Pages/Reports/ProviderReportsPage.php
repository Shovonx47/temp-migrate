<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Enums\SupermanNavigationGroup;
use App\Models\Gateway;
use BackedEnum;
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
use UnitEnum;

class ProviderReportsPage extends AbstractReportPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServer;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Reports;

    protected static ?string $navigationLabel = 'Provider Reports';

    protected static ?string $title = 'Provider Reports';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'reports/provider';

    protected string $view = 'filament.admin.reports.provider-report';

    protected function reportType(): string
    {
        return 'provider';
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
                'md' => 3,
            ])
            ->defaultSort('total_messages', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25);
    }

    protected function query(): Builder
    {
        $fromState = $this->getTableFilterState('from_date');
        $toState = $this->getTableFilterState('to_date');
        $from = $fromState['from'] ?? null;
        $until = $toState['until'] ?? null;

        $msgSub = DB::table('messages')
            ->selectRaw("
                gateway_id,
                COUNT(*) as total_messages,
                COUNT(DISTINCT batch_id) as batches_count,
                SUM(CASE WHEN status IN ('sent', 'delivered') THEN 1 ELSE 0 END) as sent_count,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
                SUM(CASE WHEN status IN ('failed', 'dlr_failed') THEN 1 ELSE 0 END) as failed_count,
                SUM(cost) as revenue,
                SUM(operator_cost) as operator_cost,
                SUM(primary_dipping_cost) as dipping_cost,
                SUM(operator_cost + primary_dipping_cost) as total_expenditure
            ")
            ->when($from, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($until, fn ($q, $until) => $q->whereDate('created_at', '<=', $until))
            ->groupBy('gateway_id');

        return Gateway::withoutGlobalScopes()
            ->leftJoinSub($msgSub, 'msg_stats', 'gateways.id', '=', 'msg_stats.gateway_id')
            ->select('gateways.*')
            ->selectRaw("
                COALESCE(msg_stats.total_messages, 0) as total_messages,
                COALESCE(msg_stats.batches_count, 0) as batches_count,
                COALESCE(msg_stats.sent_count, 0) as sent_count,
                COALESCE(msg_stats.delivered_count, 0) as delivered_count,
                COALESCE(msg_stats.failed_count, 0) as failed_count,
                COALESCE(msg_stats.revenue, 0) as revenue,
                COALESCE(msg_stats.operator_cost, 0) as operator_cost,
                COALESCE(msg_stats.dipping_cost, 0) as dipping_cost,
                COALESCE(msg_stats.total_expenditure, 0) as total_expenditure,
                (COALESCE(msg_stats.revenue, 0) - COALESCE(msg_stats.total_expenditure, 0)) as profit
            ");
    }

    /** @return array<int, TextColumn> */
    protected function columns(): array
    {
        return [
            TextColumn::make('name')
                ->label('Provider / Gateway')
                ->searchable()
                ->sortable()
                ->weight('medium'),

            TextColumn::make('driver')
                ->label('Driver')
                ->badge()
                ->sortable(),

            TextColumn::make('total_messages')
                ->label('Total SMS')
                ->numeric()
                ->alignEnd()
                ->sortable()
                ->summarize(
                    Sum::make()
                        ->label('Total')
                        ->numeric()
                ),

            TextColumn::make('batches_count')
                ->label('Bulk Batches')
                ->numeric()
                ->alignEnd()
                ->sortable()
                ->summarize(
                    Sum::make()
                        ->label('Total')
                        ->numeric()
                ),

            TextColumn::make('sent_count')
                ->label('Dispatched')
                ->numeric()
                ->alignEnd()
                ->sortable()
                ->summarize(
                    Sum::make()
                        ->label('Total')
                        ->numeric()
                ),

            TextColumn::make('delivered_count')
                ->label('Delivered')
                ->numeric()
                ->alignEnd()
                ->sortable()
                ->summarize(
                    Sum::make()
                        ->label('Total')
                        ->numeric()
                ),

            TextColumn::make('failed_count')
                ->label('Failed')
                ->numeric()
                ->alignEnd()
                ->sortable()
                ->summarize(
                    Sum::make()
                        ->label('Total')
                        ->numeric()
                ),

            TextColumn::make('revenue')
                ->label('Customer Spend')
                ->money('BDT')
                ->alignEnd()
                ->sortable()
                ->summarize(
                    Sum::make()
                        ->label('Total')
                        ->money('BDT')
                ),

            TextColumn::make('operator_cost')
                ->label('Gateway Cost')
                ->money('BDT')
                ->alignEnd()
                ->sortable()
                ->summarize(
                    Sum::make()
                        ->label('Total')
                        ->money('BDT')
                ),

            TextColumn::make('dipping_cost')
                ->label('Dipping Cost')
                ->money('BDT')
                ->alignEnd()
                ->sortable()
                ->summarize(
                    Sum::make()
                        ->label('Total')
                        ->money('BDT')
                ),

            TextColumn::make('total_expenditure')
                ->label('Total Expenditure')
                ->money('BDT')
                ->weight('medium')
                ->alignEnd()
                ->sortable()
                ->summarize(
                    Sum::make()
                        ->label('Total')
                        ->money('BDT')
                ),

            TextColumn::make('profit')
                ->label('Profit / Margin')
                ->money('BDT')
                ->color(fn ($state): ?string => (float) $state >= 0 ? null : 'danger')
                ->alignEnd()
                ->sortable()
                ->summarize(
                    Sum::make()
                        ->label('Total')
                        ->money('BDT')
                ),

            TextColumn::make('provider_balance')
                ->label('Provider Balance')
                ->state(function (Gateway $record): string {
                    $metricsService = app(\App\Services\Analytics\GatewayMetricsService::class);
                    $val = $metricsService->getMetrics($record)['provider_balance'] ?? null;

                    return $val !== null ? (is_numeric($val) ? number_format((float) $val, 2).' BDT' : (string) $val) : '—';
                })
                ->alignEnd()
                ->toggleable(isToggledHiddenByDefault: false),

            TextColumn::make('health_status')
                ->label('Health')
                ->badge()
                ->placeholder('Unknown')
                ->color(fn (?string $state): string => match (strtolower($state ?? '')) {
                    'healthy' => 'success',
                    'down' => 'danger',
                    default => 'gray',
                })
                ->toggleable(isToggledHiddenByDefault: false),

            TextColumn::make('is_enabled')
                ->label('Enabled')
                ->badge()
                ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                ->toggleable(isToggledHiddenByDefault: false),
        ];
    }

    /** @return array<int, Filter|SelectFilter> */
    protected function filters(): array
    {
        return [
            SelectFilter::make('gateway_id')
                ->label('Provider / Gateway')
                ->placeholder('All Gateways')
                ->options(fn () => Gateway::withoutGlobalScopes()->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->query(function (Builder $query, array $data): Builder {
                    $val = $data['value'] ?? null;

                    return $query->when($val, fn (Builder $q) => $q->where('gateways.id', $val));
                }),

            Filter::make('from_date')
                ->schema([
                    DatePicker::make('from')
                        ->label('From Date')
                        ->placeholder('Select date')
                        ->native(false)
                        ->closeOnDateSelection(),
                ])
                ->query(fn (Builder $query): Builder => $query)
                ->indicateUsing(function (array $data): array {
                    return ! empty($data['from'])
                        ? ['From: '.Carbon::parse($data['from'])->toFormattedDateString()]
                        : [];
                }),

            Filter::make('to_date')
                ->schema([
                    DatePicker::make('until')
                        ->label('To Date')
                        ->placeholder('Select date')
                        ->native(false)
                        ->closeOnDateSelection(),
                ])
                ->query(fn (Builder $query): Builder => $query)
                ->indicateUsing(function (array $data): array {
                    return ! empty($data['until'])
                        ? ['To: '.Carbon::parse($data['until'])->toFormattedDateString()]
                        : [];
                }),
        ];
    }

    public function getTimeframeLabel(): string
    {
        $fromState = $this->getTableFilterState('from_date');
        $toState = $this->getTableFilterState('to_date');
        $from = $fromState['from'] ?? null;
        $until = $toState['until'] ?? null;

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

        return 'Showing all-time cumulative data (Default)';
    }

    /**
     * @return array{
     *     total_messages: int,
     *     batches_count: int,
     *     sent_count: int,
     *     delivered_count: int,
     *     failed_count: int,
     *     delivery_rate: float,
     *     total_revenue: float,
     *     total_operator_cost: float,
     *     total_dipping_cost: float,
     *     total_expenditure: float,
     *     total_profit: float,
     *     margin_rate: float
     * }
     */
    public function getTotalCalculationData(): array
    {
        $query = isset($this->table) ? $this->getFilteredTableQuery() : $this->query();
        $baseQuery = (clone $query)->reorder()->toBase();

        $totals = DB::connection()->table($baseQuery, 'gateways')
            ->selectRaw("
                COALESCE(SUM(total_messages), 0) as total_messages,
                COALESCE(SUM(batches_count), 0) as batches_count,
                COALESCE(SUM(sent_count), 0) as sent_count,
                COALESCE(SUM(delivered_count), 0) as delivered_count,
                COALESCE(SUM(failed_count), 0) as failed_count,
                COALESCE(SUM(revenue), 0) as total_revenue,
                COALESCE(SUM(operator_cost), 0) as total_operator_cost,
                COALESCE(SUM(dipping_cost), 0) as total_dipping_cost,
                COALESCE(SUM(total_expenditure), 0) as total_expenditure,
                COALESCE(SUM(profit), 0) as total_profit
            ")
            ->first();

        $totalMessages = (int) ($totals->total_messages ?? 0);
        $deliveredCount = (int) ($totals->delivered_count ?? 0);
        $totalRevenue = (float) ($totals->total_revenue ?? 0);
        $totalProfit = (float) ($totals->total_profit ?? 0);

        return [
            'total_messages' => $totalMessages,
            'batches_count' => (int) ($totals->batches_count ?? 0),
            'sent_count' => (int) ($totals->sent_count ?? 0),
            'delivered_count' => $deliveredCount,
            'failed_count' => (int) ($totals->failed_count ?? 0),
            'delivery_rate' => $totalMessages > 0 ? round(($deliveredCount / $totalMessages) * 100, 1) : 0.0,
            'total_revenue' => $totalRevenue,
            'total_operator_cost' => (float) ($totals->total_operator_cost ?? 0),
            'total_dipping_cost' => (float) ($totals->total_dipping_cost ?? 0),
            'total_expenditure' => (float) ($totals->total_expenditure ?? 0),
            'total_profit' => $totalProfit,
            'margin_rate' => $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0.0,
        ];
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            'Provider / Gateway',
            'Driver',
            'Total SMS',
            'Bulk Batches',
            'Dispatched',
            'Delivered',
            'Failed',
            'Customer Spend (BDT)',
            'Gateway Cost (BDT)',
            'Dipping Cost (BDT)',
            'Total Expenditure (BDT)',
            'Profit / Margin (BDT)',
            'Health',
            'Enabled',
        ];
    }

    public function reportRows(): Collection
    {
        $query = isset($this->table) ? $this->getFilteredTableQuery() : $this->query();
        $rows = $query->get()->map(fn (Gateway $record): array => [
            $record->name,
            $record->driver?->value ?? (string) $record->driver,
            (int) ($record->total_messages ?? 0),
            (int) ($record->batches_count ?? 0),
            (int) ($record->sent_count ?? 0),
            (int) ($record->delivered_count ?? 0),
            (int) ($record->failed_count ?? 0),
            number_format((float) ($record->revenue ?? 0), 2, '.', ''),
            number_format((float) ($record->operator_cost ?? 0), 2, '.', ''),
            number_format((float) ($record->dipping_cost ?? 0), 2, '.', ''),
            number_format((float) ($record->total_expenditure ?? 0), 2, '.', ''),
            number_format((float) ($record->profit ?? 0), 2, '.', ''),
            $record->health_status ?? 'Unknown',
            $record->is_enabled ? 'Yes' : 'No',
        ]);

        if ($rows->isNotEmpty()) {
            $totals = $this->getTotalCalculationData();
            $rows->push([
                'TOTAL',
                '—',
                $totals['total_messages'],
                $totals['batches_count'],
                $totals['sent_count'],
                $totals['delivered_count'],
                $totals['failed_count'],
                number_format($totals['total_revenue'], 2, '.', ''),
                number_format($totals['total_operator_cost'], 2, '.', ''),
                number_format($totals['total_dipping_cost'], 2, '.', ''),
                number_format($totals['total_expenditure'], 2, '.', ''),
                number_format($totals['total_profit'], 2, '.', ''),
                '—',
                '—',
            ]);
        }

        return $rows;
    }
}
