<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\HasDateFilter;
use App\Models\Message;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class GatewayPerformanceChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Gateway Performance';

    protected ?string $description = 'Messages per gateway';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $data = Message::withoutGlobalScopes()
            ->where('messages.created_at', '>=', $from)
            ->where('messages.created_at', '<=', $to)
            ->join('gateways', 'messages.gateway_id', '=', 'gateways.id')
            ->selectRaw('gateways.name as gateway, COUNT(*) as count')
            ->groupBy('gateways.name')
            ->get();

        $colors = ['#10b981', '#0ea5e9', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

        return [
            'datasets' => [
                [
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $data->pluck('gateway')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
