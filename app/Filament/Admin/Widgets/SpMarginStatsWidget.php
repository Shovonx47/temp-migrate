<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\HasDateFilter;
use App\Models\User;
use App\Services\Analytics\SpMarginCalculationService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SpMarginStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = '60s';

    protected int|array|null $columns = 4;

    public function getStats(): array
    {
        $service = app(SpMarginCalculationService::class);
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);
        $selectedSpId = $this->pageFilters['sp_id'] ?? null;

        if ($selectedSpId && ($sp = User::withoutGlobalScopes()->find($selectedSpId))) {
            $period = $service->calculateForSp($sp, $from, $to);
        } else {
            $period = $service->calculateForPeriod($from, $to);
        }

        return [
            Stat::make('Retail Revenue', number_format($period['revenue'], 2).' BDT')
                ->description($period['messages'].' messages')
                ->color('success')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Wholesale Cost', number_format($period['wholesale_cost'], 2).' BDT')
                ->description('S P cost basis')
                ->color('warning')
                ->icon('heroicon-o-arrow-trending-down'),
            Stat::make('S P Margin', number_format($period['margin'], 2).' BDT')
                ->description($period['margin_percent'].'% of retail')
                ->color($period['margin'] >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-chart-bar'),
            Stat::make('Messages', number_format($period['messages']))
                ->description('Via S P customers')
                ->color('primary')
                ->icon('heroicon-o-chat-bubble-left-right'),
        ];
    }
}

