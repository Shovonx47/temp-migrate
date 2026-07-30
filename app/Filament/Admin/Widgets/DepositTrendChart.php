<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\HasDateFilter;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class DepositTrendChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Deposit Trend';

    protected ?string $description = 'Daily deposit amounts';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $data = DB::table('deposits')
            ->where('status', 'completed')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Deposits (BDT)',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.6)',
                    'borderColor' => '#10b981',
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
