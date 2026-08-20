<?php

namespace App\Filament\Customer\Widgets;

use App\Filament\Concerns\HasDateFilter;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MessageStatsByPeriodWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = '60s';

    protected int|array|null $columns = 3;

    public function getStats(): array
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $stats = auth()->user()->messages()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END), 0) as delivered")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END), 0) as failed")
            ->selectRaw("COALESCE(SUM(CASE WHEN status != 'failed' THEN cost ELSE 0 END), 0) as total_cost")
            ->selectRaw('COALESCE(AVG(parts), 0) as avg_parts')
            ->selectRaw('COALESCE(SUM(is_unicode), 0) as unicode')
            ->first();

        $total = (int) $stats->total;
        $delivered = (int) $stats->delivered;
        $failed = (int) $stats->failed;
        $unicode = (int) $stats->unicode;

        $deliveryRate = $total > 0 ? round(($delivered / $total) * 100, 1) : 0;

        return [
            Stat::make('Total Messages', number_format($total))
                ->description('In selected period')
                ->icon('heroicon-o-paper-airplane'),

            Stat::make('Delivery Rate', $deliveryRate.'%')
                ->description(number_format($delivered).' delivered')
                ->color($deliveryRate >= 90 ? 'success' : ($deliveryRate >= 70 ? 'warning' : 'danger'))
                ->icon('heroicon-o-check-badge'),

            Stat::make('Failed', number_format($failed))
                ->description($total > 0 ? round(($failed / $total) * 100, 1).'% failure rate' : '0% failure rate')
                ->color($failed > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-x-circle'),

            Stat::make('Total Cost', number_format((float) $stats->total_cost, 2).' BDT')
                ->description('Your spend')
                ->color('warning')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Avg Parts', number_format((float) $stats->avg_parts, 1))
                ->description('SMS segments per message')
                ->icon('heroicon-o-bars-3-bottom-left'),

            Stat::make('Unicode', number_format($unicode))
                ->description($total > 0 ? round(($unicode / $total) * 100, 1).'% of messages' : '0% of messages')
                ->icon('heroicon-o-language'),
        ];
    }
}
