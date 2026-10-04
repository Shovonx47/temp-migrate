<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\HasDateFilter;
use App\Services\Analytics\SpMarginCalculationService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class SpMarginBreakdownWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'S P Margin by Partner';

    protected ?string $description = 'Retail revenue vs wholesale cost per S P';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        $service = app(SpMarginCalculationService::class);
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);
        $rows = array_slice($service->getSpBreakdown($from, $to), 0, 12);

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => array_column($rows, 'revenue'),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.7)',
                ],
                [
                    'label' => 'Wholesale',
                    'data' => array_column($rows, 'wholesale_cost'),
                    'backgroundColor' => 'rgba(245, 158, 11, 0.7)',
                ],
                [
                    'label' => 'Margin',
                    'data' => array_column($rows, 'margin'),
                    'backgroundColor' => 'rgba(14, 165, 233, 0.7)',
                ],
            ],
            'labels' => array_column($rows, 'sp_name'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
