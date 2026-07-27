<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Analytics\ProfitCalculationService;
use Filament\Widgets\ChartWidget;

class ProfitTrendChart extends ChartWidget
{
    protected ?string $heading = 'Profit Trend';

    protected ?string $description = 'Daily revenue, cost, and profit (last 30 days)';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        $service = app(ProfitCalculationService::class);
        $trend = $service->getDailyTrend(30);

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
