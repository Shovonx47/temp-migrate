<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\HasDateFilter;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class DeliveryRateTrendChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Delivery Rate Trend';

    protected ?string $description = 'Daily delivery rate %';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $data = DB::table('messages')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw("DATE(created_at) as date, COUNT(*) as total, SUM(CASE WHEN status IN ('sent', 'delivered') THEN 1 ELSE 0 END) as successful")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'rate' => $row->total > 0 ? round(($row->successful / $row->total) * 100, 1) : 0,
            ]);

        return [
            'datasets' => [
                [
                    'label' => 'Success Rate %',
                    'data' => $data->pluck('rate')->toArray(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $data->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
