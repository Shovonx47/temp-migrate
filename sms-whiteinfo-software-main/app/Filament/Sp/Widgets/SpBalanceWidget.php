<?php

namespace App\Filament\Sp\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class SpBalanceWidget extends BaseWidget
{
    public function getStats(): array
    {
        $user = auth()->user();
        $sp = ($user?->type?->isSp() && $user?->sp_id) ? User::find($user->sp_id) : $user;
        $balance = $sp ? (float) $sp->balance : 0;

        return [
            Stat::make('Current Balance', Number::currency($balance, 'BDT'))
                ->description('Available to distribute to customers')
                ->color($balance > 0 ? 'success' : 'danger'),
        ];
    }
}
