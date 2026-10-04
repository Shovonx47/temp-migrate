<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class RevenueTrendChart extends ChartWidget
{
    protected ?string $heading = 'Revenue Trends';

    protected ?string $description = 'Deposit amounts per day (last 30 days)';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        $data = Deposit::withoutGlobalScopes()
            ->where('status', DepositStatus::Completed)
            ->where('paid_at', '>=', now()->subDays(30))
            ->toBase()
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (BDT)',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#10b981',
                    'borderRadius' => 4,
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
