<?php

namespace App\Filament\Customer\Widgets;

use App\Filament\Concerns\HasDateFilter;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BillingStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = '60s';

    protected int|array|null $columns = 4;

    public function getStats(): array
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $user = auth()->user();

        $deposits = $user->deposits()
            ->where('status', 'completed')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->sum('amount');

        $spent = $user->messages()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->where('status', '!=', 'failed')
            ->sum('cost');

        $pendingDeposits = $user->deposits()
            ->where('status', 'pending')
            ->sum('amount');

        $transactions = $user->transactions()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->count();

        return [
            Stat::make('Deposits', number_format($deposits, 2).' BDT')
                ->description('Completed in period')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Spent', number_format($spent, 2).' BDT')
                ->description('Message cost in period')
                ->color('danger')
                ->icon('heroicon-o-arrow-trending-down'),

            Stat::make('Pending Deposits', number_format($pendingDeposits, 2).' BDT')
                ->description('Awaiting approval')
                ->color('warning')
                ->icon('heroicon-o-clock'),

            Stat::make('Transactions', number_format($transactions))
                ->description('In selected period')
                ->icon('heroicon-o-arrows-right-left'),
        ];
    }
}
