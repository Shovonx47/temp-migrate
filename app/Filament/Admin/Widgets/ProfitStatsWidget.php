<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Analytics\ProfitCalculationService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProfitStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected int|array|null $columns = 4;

    public function getStats(): array
    {
        $service = app(ProfitCalculationService::class);
        $summary = $service->getSummary();

        $today = $summary['today'];
        $thisMonth = $summary['this_month'];
        $overall = $summary['overall'];

        return [
            Stat::make('Revenue Today', number_format($today['revenue'], 2).' BDT')
                ->description($today['messages'].' messages')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Cost Today', number_format($today['cost'], 2).' BDT')
                ->description('Gateway cost')
                ->color('danger')
                ->icon('heroicon-o-arrow-trending-down'),

            Stat::make('Profit Today', number_format($today['profit'], 2).' BDT')
                ->description($today['margin'].'% margin')
                ->color($today['profit'] >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-chart-bar'),

            Stat::make('Profit This Month', number_format($thisMonth['profit'], 2).' BDT')
                ->description($thisMonth['margin'].'% margin')
                ->color($thisMonth['profit'] >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-calendar'),

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
