<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\HasDateFilter;
use App\Models\Message;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MessageStatsByPeriodWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = '60s';

    protected int|array|null $columns = 4;

    public function getStats(): array
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $baseQuery = Message::withoutGlobalScopes()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to);

        $total = (clone $baseQuery)->count();
        $delivered = (clone $baseQuery)->where('status', 'delivered')->count();
        $failed = (clone $baseQuery)->where('status', 'failed')->count();
        $deliveryRate = $total > 0 ? round(($delivered / $total) * 100, 1) : 0;
        $totalCost = (clone $baseQuery)->sum('cost');
        $avgParts = (clone $baseQuery)->avg('parts') ?? 0;
        $unicode = (clone $baseQuery)->where('is_unicode', true)->count();

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

            Stat::make('Total Cost', number_format($totalCost, 2).' BDT')
                ->description('Customer revenue')
                ->color('warning')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Avg Parts', number_format($avgParts, 1))
                ->description('SMS segments per message')
                ->icon('heroicon-o-bars-3-bottom-left'),

            Stat::make('Unicode', number_format($unicode))
                ->description($total > 0 ? round(($unicode / $total) * 100, 1).'% of messages' : '0% of messages')
                ->icon('heroicon-o-language'),
        ];
    }
}
