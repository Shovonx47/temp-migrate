<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\HasDateFilter;
use App\Models\Deposit;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class DepositMethodChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Deposits by Method';

    protected ?string $description = 'Payment method distribution';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $data = Deposit::withoutGlobalScopes()
            ->where('status', 'completed')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Amount (BDT)',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => ['#10b981', '#0ea5e9', '#f59e0b'],
                ],
            ],
            'labels' => $data->pluck('payment_method')->map(fn ($m) => strtoupper($m))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
