<?php

namespace App\Filament\Customer\Widgets;

use App\Filament\Concerns\HasDateFilter;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class SpendingTrendChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Spending Trends';

    protected ?string $description = 'Cost per day in the selected period';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $data = auth()->user()->messages()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->where('status', '!=', 'failed')
            ->toBase()
            ->selectRaw('DATE(created_at) as date, SUM(cost) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Cost (BDT)',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => '#0ea5e9',
                    'borderColor' => '#0ea5e9',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $data->pluck('date')->map(fn ($date) => Carbon::parse($date)->format('M d'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
