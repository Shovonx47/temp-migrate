<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Message;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MessageStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected int|array|null $columns = 4;

    public function getStats(): array
    {
        $todayMessages = Message::withoutGlobalScopes()
            ->whereDate('created_at', today())
            ->count();

        $todayFailed = Message::withoutGlobalScopes()
            ->whereDate('created_at', today())
            ->where('status', 'failed')
            ->count();

        $todayDelivered = Message::withoutGlobalScopes()
            ->whereDate('created_at', today())
            ->where('status', 'delivered')
            ->count();

        $deliveryRate = $todayMessages > 0
            ? round(($todayDelivered / $todayMessages) * 100, 1)
            : 0;

        $todayCost = Message::withoutGlobalScopes()
            ->whereDate('created_at', today())
            ->sum('cost');

        return [
            Stat::make('Messages Today', number_format($todayMessages))
                ->description('Sent today')
                ->icon('heroicon-o-paper-airplane'),

            Stat::make('Delivery Rate', $deliveryRate.'%')
                ->description('Success rate today')
                ->color('success')
                ->icon('heroicon-o-check-badge'),

            Stat::make('Failed Today', number_format($todayFailed))
                ->description('Delivery failures')
                ->color('danger')
                ->icon('heroicon-o-x-circle'),

            Stat::make('Cost Today', number_format($todayCost, 2).' BDT')
                ->description('Today\'s spend')
                ->color('warning')
                ->icon('heroicon-o-currency-dollar'),
        ];
    }
}
