<?php

namespace App\Filament\Customer\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class SpendingTrendChart extends ChartWidget
{
    protected ?string $heading = 'Spending Trends';

    protected ?string $description = 'Cost per day (last 30 days)';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        $data = auth()->user()->messages()
            ->where('sent_at', '>=', now()->subDays(30))
            ->toBase()
            ->selectRaw('DATE(sent_at) as date, SUM(cost) as total')
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
