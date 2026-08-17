<?php

namespace App\Filament\Sp\Widgets;

use App\Models\SpDistribution;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class SpStatsWidget extends BaseWidget
{
    public function getStats(): array
    {
        $user = auth()->user();
        $sp = ($user?->type?->isSp() && $user?->sp_id) ? User::find($user->sp_id) : $user;
        $customerCount = $sp ? $sp->spCustomers()->count() : 0;
        $totalDistributed = $sp
            ? (float) SpDistribution::query()->where('sp_id', $sp->id)->sum('amount')
            : 0;
        $balance = $sp ? (float) $sp->balance : 0;

        return [
            Stat::make('Customers', Number::format($customerCount))
                ->description('Under this S P')
                ->color('info'),
            Stat::make('Total Distributed', Number::currency($totalDistributed, 'BDT'))
                ->description('All-time distributions')
                ->color('warning'),
            Stat::make('Remaining Balance', Number::currency($balance, 'BDT'))
                ->description('Available in wallet')
                ->color($balance > 0 ? 'success' : 'danger'),
        ];
    }
}
