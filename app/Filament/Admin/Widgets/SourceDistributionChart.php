<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\HasDateFilter;
use App\Models\Message;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class SourceDistributionChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Source Distribution';

    protected ?string $description = 'Messages by source (web vs API)';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $data = Message::withoutGlobalScopes()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->get();

        return [
            'datasets' => [
                [
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => ['#10b981', '#0ea5e9', '#f59e0b'],
                ],
            ],
            'labels' => $data->pluck('source')->map(fn ($s) => strtoupper($s instanceof \BackedEnum ? $s->value : (string) $s))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
