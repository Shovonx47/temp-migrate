<?php

namespace App\Filament\Customer\Widgets;

use App\Filament\Concerns\HasDateFilter;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class DeliveryRateTrendChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Delivery Rate Trend';

    protected ?string $description = 'Daily delivery rate in the selected period';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $data = auth()->user()->messages()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->toBase()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('sent', 'delivered') THEN 1 ELSE 0 END), 0) as successful")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Success Rate (%)',
                    'data' => $data->map(fn ($row) => $row->total > 0
                        ? round(($row->successful / $row->total) * 100, 1)
                        : 0)->toArray(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $data->pluck('date')->map(fn ($date) => Carbon::parse($date)->format('M d'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
