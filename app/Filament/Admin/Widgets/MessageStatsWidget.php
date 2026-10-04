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

        $todaySuccessful = Message::withoutGlobalScopes()
            ->whereDate('created_at', today())
            ->whereIn('status', ['sent', 'delivered'])
            ->count();

        $todayDelivered = Message::withoutGlobalScopes()
            ->whereDate('created_at', today())
            ->where('status', 'delivered')
            ->count();

        $todayFailed = Message::withoutGlobalScopes()
            ->whereDate('created_at', today())
            ->whereIn('status', ['failed', 'dlr_failed'])
            ->count();

        $successRate = $todayMessages > 0
            ? round(($todaySuccessful / $todayMessages) * 100, 1)
            : 0;

        $todayCost = Message::withoutGlobalScopes()
            ->whereDate('created_at', today())
            ->sum('cost');

        return [
            Stat::make('Messages Today', number_format($todayMessages))
                ->description('Sent today')
                ->icon('heroicon-o-paper-airplane'),

            Stat::make('Success Rate', $successRate.'%')
                ->description(number_format($todaySuccessful).' sent or delivered')
                ->color($successRate >= 90 ? 'success' : ($successRate >= 70 ? 'warning' : 'danger'))
                ->icon('heroicon-o-check-badge'),

            Stat::make('Delivered', number_format($todayDelivered))
                ->description('Confirmed by carrier')
                ->color($todayDelivered > 0 ? 'success' : 'gray')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Failed Today', number_format($todayFailed))
                ->description('Send & delivery failures')
                ->color($todayFailed > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-x-circle'),

            Stat::make('Cost Today', number_format($todayCost, 2).' BDT')
                ->description('Today\'s spend')
                ->color('warning')
                ->icon('heroicon-o-currency-dollar'),
        ];
    }
}
