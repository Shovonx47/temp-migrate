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

        $todayProcessing = Message::withoutGlobalScopes()
            ->whereDate('created_at', today())
            ->where('status', 'sending')
            ->count();

        $dlrReceived = Message::withoutGlobalScopes()
            ->whereDate('created_at', today())
            ->whereNotNull('delivered_at')
            ->count();

        $dlrPending = Message::withoutGlobalScopes()
            ->whereDate('created_at', today())
            ->whereNotNull('sent_at')
            ->whereNull('delivered_at')
            ->where('status', '!=', 'failed')
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

            Stat::make('Processing', number_format($todayProcessing))
                ->description('Currently sending')
                ->color('info')
                ->icon('heroicon-o-arrow-path'),

            Stat::make('DLR Received', number_format($dlrReceived))
                ->description('Confirmations received')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('DLR Pending', number_format($dlrPending))
                ->description('Awaiting confirmation')
                ->color('warning')
                ->icon('heroicon-o-clock'),

            Stat::make('Cost Today', number_format($todayCost, 2).' BDT')
                ->description('Today\'s spend')
                ->color('warning')
                ->icon('heroicon-o-currency-dollar'),
        ];
    }
}
