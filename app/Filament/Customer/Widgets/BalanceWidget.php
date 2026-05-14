<?php

namespace App\Filament\Customer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BalanceWidget extends BaseWidget
{
    public function getStats(): array
    {
        $user = auth()->user();
        $balance = $user ? (float) $user->balance : 0;

        return [
            Stat::make('Current Balance', number_format($balance, 2).' BDT')
                ->description('Available for campaigns')
                ->color($balance > 0 ? 'success' : 'danger'),
        ];
    }
}
