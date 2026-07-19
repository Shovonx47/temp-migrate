<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Message;
use Filament\Widgets\ChartWidget;

class GatewayPerformanceChart extends ChartWidget
{
    protected ?string $heading = 'Gateway Performance';

    protected ?string $description = 'Messages per gateway (last 30 days)';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        $data = Message::withoutGlobalScopes()
            ->where('sent_at', '>=', now()->subDays(30))
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
