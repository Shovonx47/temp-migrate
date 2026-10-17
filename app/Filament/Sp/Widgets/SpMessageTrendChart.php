<?php

namespace App\Filament\Sp\Widgets;

use App\Filament\Concerns\HasDateFilter;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class SpMessageTrendChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Message Traffic Trend';

    protected ?string $description = 'Daily messages sent by your customers';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        /** @var User $actingUser */
        $actingUser = Filament::auth()->user();
        $sp = ($actingUser->type->isSp() && $actingUser->sp_id) ? (User::find($actingUser->sp_id) ?? $actingUser) : $actingUser;

        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $customerIds = $sp->spCustomers()->pluck('id');

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
                    'label' => 'Total Sent',
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
