<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\HasDateFilter;
use App\Models\Deposit;
use App\Models\Message;
use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = '60s';

    protected int|array|null $columns = 3;

    public function getStats(): array
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $deposits = Deposit::withoutGlobalScopes()
            ->where('status', 'completed')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->sum('amount');

        $spent = Message::withoutGlobalScopes()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->sum('cost');

        $gatewayCost = Message::withoutGlobalScopes()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->sum('gateway_cost');

        $txCount = Transaction::withoutGlobalScopes()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->count();

        $systemBalance = User::withoutGlobalScopes()
            ->customers()
            ->sum('balance');

        $pendingDeposits = Deposit::withoutGlobalScopes()
            ->where('status', 'pending')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->sum('amount');

        return [
            Stat::make('Deposits', number_format($deposits, 2).' BDT')
                ->description('Completed in period')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Customer Spend', number_format($spent, 2).' BDT')
                ->description('Message revenue')
                ->color('warning')
                ->icon('heroicon-o-shopping-cart'),

            Stat::make('Gateway Cost', number_format($gatewayCost, 2).' BDT')
                ->description('Platform cost')
                ->color('danger')
                ->icon('heroicon-o-arrow-trending-down'),

            Stat::make('Pending Deposits', number_format($pendingDeposits, 2).' BDT')
                ->description('Awaiting approval')
                ->color('info')
                ->icon('heroicon-o-clock'),

            Stat::make('Transactions', number_format($txCount))
                ->description('In selected period')
                ->icon('heroicon-o-arrows-right-left'),

            Stat::make('System Balance', number_format($systemBalance, 2).' BDT')
                ->description('Total user balances')
                ->color('primary')
                ->icon('heroicon-o-wallet'),
        ];
    }
}
