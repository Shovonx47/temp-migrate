<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\HasDateFilter;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class SpAdminTrafficTrendChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Reseller Traffic Trend';

    protected ?string $description = 'Daily messages sent by S P customers';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);
        $selectedSpId = $this->pageFilters['sp_id'] ?? null;

        $spQuery = User::withoutGlobalScopes()->sps()->whereNull('sp_id');
        if ($selectedSpId) {
            $spQuery->where('id', $selectedSpId);
        }
        $spIds = $spQuery->pluck('id');

        $customerIds = User::withoutGlobalScopes()->customers()->whereIn('sp_id', $spIds)->pluck('id');

        $rows = DB::table('messages')
            ->whereIn('user_id', $customerIds)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN status = \'delivered\' THEN 1 ELSE 0 END) as delivered
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = $rows->pluck('date')->toArray();
        $totalData = $rows->pluck('total')->map(fn ($v) => (int) $v)->toArray();
        $deliveredData = $rows->pluck('delivered')->map(fn ($v) => (int) $v)->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Total Messages',
                    'data' => $totalData,
                    'borderColor' => '#0ea5e9',
                    'backgroundColor' => 'rgba(14, 165, 233, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Delivered',
                    'data' => $deliveredData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
