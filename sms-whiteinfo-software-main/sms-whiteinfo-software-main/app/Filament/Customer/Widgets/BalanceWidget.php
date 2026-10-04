<?php

namespace App\Filament\Customer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class BalanceWidget extends BaseWidget
{
    public function getStats(): array
    {
        $user = auth()->user();
        $balance = $user ? (float) $user->balance : 0;

        return [
            Stat::make('Current Balance', Number::currency($balance, 'BDT'))
                ->description('Available for campaigns')
                ->color($balance > 0 ? 'success' : 'danger'),
        ];
    }
}
