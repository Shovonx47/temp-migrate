<?php

namespace App\Filament\Sp\Widgets;

use App\Filament\Concerns\HasDateFilter;
use App\Services\Analytics\SpMarginCalculationService;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SpOwnMarginStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = '60s';

    protected int|array|null $columns = 4;

    public function getStats(): array
    {
        $sp = Filament::auth()->user();
        $service = app(SpMarginCalculationService::class);
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);
        $period = $service->calculateForSp($sp, $from, $to);

        return [
            Stat::make('Customer Spend', number_format($period['revenue'], 2).' BDT')
                ->description($period['messages'].' messages')
                ->color('success')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Your Wholesale Cost', number_format($period['wholesale_cost'], 2).' BDT')
                ->description('Charged at your rates')
                ->color('warning')
                ->icon('heroicon-o-arrow-trending-down'),
            Stat::make('Your Margin', number_format($period['margin'], 2).' BDT')
                ->description($period['margin_percent'].'% of retail')
                ->color($period['margin'] >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-chart-bar'),
            Stat::make('Messages', number_format($period['messages']))
                ->description('From your customers')
                ->color('primary')
                ->icon('heroicon-o-chat-bubble-left-right'),
        ];
    }
}
