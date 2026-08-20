<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\HasDateFilter;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class SpendingTrendChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Spending Trend';

    protected ?string $description = 'Daily customer spending';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $data = DB::table('messages')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw('DATE(created_at) as date, SUM(cost) as revenue, SUM(primary_dipping_cost) as cost')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (BDT)',
                    'data' => $data->pluck('revenue')->toArray(),
                    'backgroundColor' => 'rgba(14, 165, 233, 0.6)',
                    'borderColor' => '#0ea5e9',
                ],
                [
                    'label' => 'Gateway Cost (BDT)',
                    'data' => $data->pluck('cost')->toArray(),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.6)',
                    'borderColor' => '#ef4444',
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
