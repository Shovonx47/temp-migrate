<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\HasDateFilter;
use App\Models\Message;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class FailedReasonWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Failed Reason Summary';

    protected ?string $description = 'Top failure reasons';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $data = Message::withoutGlobalScopes()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->where('status', 'failed')
            ->whereNotNull('failed_reason')
            ->selectRaw('failed_reason, COUNT(*) as count')
            ->groupBy('failed_reason')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $colors = ['#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16', '#22c55e', '#14b8a6', '#06b6d4', '#3b82f6', '#8b5cf6'];

        return [
            'datasets' => [
                [
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => $colors,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $data->pluck('failed_reason')->map(fn ($r) => mb_strimwidth($r, 0, 40, '...'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
