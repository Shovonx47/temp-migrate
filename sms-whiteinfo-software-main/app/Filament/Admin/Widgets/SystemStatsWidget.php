<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Batch;
use App\Models\Message;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class SystemStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected int|array|null $columns = 4;

    public function getStats(): array
    {
        $totalUsers = User::withoutGlobalScopes()->count();
        $activeCustomers = User::withoutGlobalScopes()->customers()->count();
        $totalMessages = Message::withoutGlobalScopes()->count();
        $totalBatches = Batch::withoutGlobalScopes()->count();

        return [
            Stat::make('Total Users', Number::abbreviate($totalUsers))
                ->description('Registered users')
                ->icon('heroicon-o-users'),

            Stat::make('Active Customers', Number::abbreviate($activeCustomers))
                ->description('Customer accounts')
                ->color('primary')
                ->icon('heroicon-o-user-group'),

            Stat::make('Total Messages', Number::abbreviate($totalMessages))
                ->description('All-time messages')
                ->color('info')
                ->icon('heroicon-o-chat-bubble-left-ellipsis'),

            Stat::make('Total Batches', Number::abbreviate($totalBatches))
                ->description('All-time batches')
                ->color('warning')
                ->icon('heroicon-o-server-stack'),
        ];
    }
}
