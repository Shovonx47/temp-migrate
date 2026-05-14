<?php

namespace App\Filament\Customer\Widgets;

use App\Models\Message;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MessageUsageWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    public function getStats(): array
    {
        $userId = auth()->id();
        $messages = Message::where('user_id', $userId);

        return [
            Stat::make('Total Messages', number_format($messages->count()))
                ->description('Messages sent')
                ->icon('heroicon-o-chat-bubble-left-ellipsis'),

            Stat::make('Delivered', number_format((clone $messages)->where('status', 'delivered')->count()))
                ->description('Successfully delivered')
                ->color('success')
                ->icon('heroicon-o-check-badge'),

            Stat::make('Failed', number_format((clone $messages)->where('status', 'failed')->count()))
                ->description('Delivery failures')
                ->color('danger')
                ->icon('heroicon-o-x-circle'),

            Stat::make('Total Cost', number_format((float) $messages->sum('cost'), 2).' BDT')
                ->description('Total spend')
                ->color('gray')
                ->icon('heroicon-o-currency-dollar'),
        ];
    }
}
