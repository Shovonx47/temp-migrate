<?php

namespace App\Filament\Customer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MessageUsageWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    public function getStats(): array
    {
        $stats = auth()->user()->messages()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END), 0) as delivered")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END), 0) as failed")
            ->selectRaw('COALESCE(SUM(cost), 0) as total_cost')
            ->first();

        return [
            Stat::make('Total Messages', number_format((int) $stats->total))
                ->description('Messages sent')
                ->icon('heroicon-o-chat-bubble-left-ellipsis'),

            Stat::make('Delivered', number_format((int) $stats->delivered))
                ->description('Successfully delivered')
                ->color('success')
                ->icon('heroicon-o-check-badge'),

            Stat::make('Failed', number_format((int) $stats->failed))
                ->description('Delivery failures')
                ->color('danger')
                ->icon('heroicon-o-x-circle'),

            Stat::make('Total Cost', number_format((float) $stats->total_cost, 2).' BDT')
                ->description('Total spend')
                ->color('gray')
                ->icon('heroicon-o-currency-dollar'),
        ];
    }
}
