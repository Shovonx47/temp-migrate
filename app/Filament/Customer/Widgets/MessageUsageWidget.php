<?php

namespace App\Filament\Customer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MessageUsageWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected int|array|null $columns = 4;

    public function getStats(): array
    {
        $stats = auth()->user()->messages()
            ->whereDate('created_at', today())
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('sent', 'delivered') THEN 1 ELSE 0 END), 0) as successful")
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('failed', 'dlr_failed') THEN 1 ELSE 0 END), 0) as failed")
            ->selectRaw("COALESCE(SUM(CASE WHEN status NOT IN ('failed', 'dlr_failed') THEN cost ELSE 0 END), 0) as total_cost")
            ->first();

        $total = (int) $stats->total;
        $successful = (int) $stats->successful;

        $deliveryRate = $total > 0
            ? round(($successful / $total) * 100, 1)
            : 0;

        return [
            Stat::make('Messages Today', number_format($total))
                ->description('Sent today')
                ->icon('heroicon-o-paper-airplane'),

            Stat::make('Success Rate', $deliveryRate.'%')
                ->description(number_format($successful).' sent successfully')
                ->color($deliveryRate >= 90 ? 'success' : ($deliveryRate >= 70 ? 'warning' : 'danger'))
                ->icon('heroicon-o-check-badge'),

            Stat::make('Failed Today', number_format((int) $stats->failed))
                ->description('Send & delivery failures')
                ->color((int) $stats->failed > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-x-circle'),

            Stat::make('Cost Today', number_format((float) $stats->total_cost, 2).' BDT')
                ->description('Today\'s spend')
                ->color('warning')
                ->icon('heroicon-o-currency-dollar'),
        ];
    }
}
