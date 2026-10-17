<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\HasDateFilter;
use App\Services\Analytics\ProfitCalculationService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProfitStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = '60s';

    protected int|array|null $columns = 3;

    public function getStats(): array
    {
        $service = app(ProfitCalculationService::class);
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $period = $service->calculateForPeriod($from, $to);
        $overall = $service->calculateForPeriod(now()->subYears(10), now());

        return [
            Stat::make('Period Revenue', number_format($period['revenue'], 2).' BDT')
                ->description($period['messages'].' messages')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Period Cost', number_format($period['cost'], 2).' BDT')
                ->description('Gateway cost')
                ->color('danger')
                ->icon('heroicon-o-arrow-trending-down'),

            Stat::make('Period Profit', number_format($period['profit'], 2).' BDT')
                ->description($period['margin'].'% margin')
                ->color($period['profit'] >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-chart-bar'),

            Stat::make('Total Revenue', number_format($overall['revenue'], 2).' BDT')
                ->description('All time')
                ->color('success')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Total Cost', number_format($overall['cost'], 2).' BDT')
                ->description('All time')
                ->color('danger')
                ->icon('heroicon-o-minus-circle'),

            Stat::make('Total Profit', number_format($overall['profit'], 2).' BDT')
                ->description($overall['margin'].'% margin')
                ->color($overall['profit'] >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-trophy'),
        ];
    }
}
