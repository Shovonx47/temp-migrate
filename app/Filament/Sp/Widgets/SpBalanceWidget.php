<?php

namespace App\Filament\Sp\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class SpBalanceWidget extends BaseWidget
{
    public function getStats(): array
    {
        $user = auth()->user();
        $balance = $user ? (float) $user->balance : 0;

        return [
            Stat::make('Current Balance', Number::currency($balance, 'BDT'))
                ->description('Available to distribute to customers')
                ->color($balance > 0 ? 'success' : 'danger'),
        ];
    }
}
