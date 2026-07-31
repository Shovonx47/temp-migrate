<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\HasDateFilter;
use App\Services\Analytics\ProfitCalculationService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class ProfitTrendChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Profit Trend';

    protected ?string $description = 'Daily revenue, cost, and profit';

    protected int|string|array $columnSpan = 2;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        $service = app(ProfitCalculationService::class);
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);
        $trend = $service->getDailyTrend($from, $to);

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => array_column($trend, 'revenue'),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Cost',
                    'data' => array_column($trend, 'cost'),
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Profit',
                    'data' => array_column($trend, 'profit'),
                    'borderColor' => '#0ea5e9',
                    'backgroundColor' => 'rgba(14, 165, 233, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => array_column($trend, 'date'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
