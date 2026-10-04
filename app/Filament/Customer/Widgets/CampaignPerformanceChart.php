<?php

namespace App\Filament\Customer\Widgets;

use App\Enums\BatchStatus;
use App\Filament\Concerns\HasDateFilter;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class CampaignPerformanceChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Campaign Performance';

    protected ?string $description = 'Campaigns by status in the selected period';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $data = auth()->user()->batches()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $colorMap = [
            BatchStatus::Pending->value => '#f59e0b',
            BatchStatus::Processing->value => '#3b82f6',
            BatchStatus::Completed->value => '#10b981',
            BatchStatus::Failed->value => '#ef4444',
        ];

        return [
            'datasets' => [
                [
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => $data->pluck('status')->map(fn ($status) => $colorMap[$status->value] ?? '#6b7280')->toArray(),
                ],
            ],
            'labels' => $data->pluck('status')->map(fn ($status) => $status->getLabel())->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
