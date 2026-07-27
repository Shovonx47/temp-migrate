<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Message;
use Filament\Widgets\ChartWidget;

class DlrSummaryChart extends ChartWidget
{
    protected ?string $heading = 'DLR Summary';

    protected ?string $description = 'Delivery status breakdown (last 30 days)';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        $baseQuery = Message::withoutGlobalScopes()
            ->where('created_at', '>=', now()->subDays(30));

        $delivered = (clone $baseQuery)->where('status', 'delivered')->count();
        $failed = (clone $baseQuery)->where('status', 'failed')->count();
        $sent = (clone $baseQuery)->where('status', 'sent')->count();
        $pending = (clone $baseQuery)->where('status', 'pending')->count();
        $sending = (clone $baseQuery)->where('status', 'sending')->count();

        return [
            'datasets' => [
                [
                    'data' => [$delivered, $failed, $sent, $pending, $sending],
                    'backgroundColor' => ['#10b981', '#ef4444', '#0ea5e9', '#f59e0b', '#8b5cf6'],
                ],
            ],
            'labels' => ['Delivered', 'Failed', 'Sent', 'Pending', 'Sending'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
