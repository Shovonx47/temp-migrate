<?php

namespace App\Filament\Customer\Widgets;

use App\Filament\Concerns\HasDateFilter;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class DepositMethodChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Deposits by Method';

    protected ?string $description = 'Completed deposits by payment method in the selected period';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $data = auth()->user()->deposits()
            ->where('status', 'completed')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();

        return [
            'datasets' => [
                [
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => ['#10b981', '#0ea5e9', '#f59e0b', '#8b5cf6'],
                ],
            ],
            'labels' => $data->pluck('payment_method')->map(fn ($method) => $method->getLabel())->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
