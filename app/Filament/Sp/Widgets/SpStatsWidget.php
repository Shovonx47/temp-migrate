<?php

namespace App\Filament\Sp\Widgets;

use App\Models\SpDistribution;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class SpStatsWidget extends BaseWidget
{
    public function getStats(): array
    {
        $user = auth()->user();
        $customerCount = $user ? $user->spCustomers()->count() : 0;
        $totalDistributed = $user
            ? (float) SpDistribution::query()->where('sp_id', $user->id)->sum('amount')
            : 0;
        $balance = $user ? (float) $user->balance : 0;

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
