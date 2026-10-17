<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Deposit;
use App\Models\Message;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected int|array|null $columns = 4;

    public function getStats(): array
    {
        $totalDeposits = Deposit::withoutGlobalScopes()
            ->where('status', 'completed')
            ->sum('amount');

        $pendingDeposits = Deposit::withoutGlobalScopes()
            ->where('status', 'pending')
            ->sum('amount');

        $totalSpent = Message::withoutGlobalScopes()->sum('cost');

        $systemBalance = User::withoutGlobalScopes()
            ->customers()
            ->sum('balance');

        return [
            Stat::make('Total Deposits', number_format($totalDeposits, 2).' BDT')
                ->description('Completed deposits')
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Pending Deposits', number_format($pendingDeposits, 2).' BDT')
                ->description('Awaiting approval')
                ->color('warning')
                ->icon('heroicon-o-clock'),

            Stat::make('Total Spent', number_format($totalSpent, 2).' BDT')
                ->description('Message costs')
                ->color('danger')
                ->icon('heroicon-o-arrow-trending-down'),

            Stat::make('System Balance', number_format($systemBalance, 2).' BDT')
                ->description('Total user balances')
                ->color('primary')
                ->icon('heroicon-o-wallet'),
        ];
    }
}
