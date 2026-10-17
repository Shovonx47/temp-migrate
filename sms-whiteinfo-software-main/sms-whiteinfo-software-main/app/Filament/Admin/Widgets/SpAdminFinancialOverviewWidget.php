<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\DepositStatus;
use App\Filament\Concerns\HasDateFilter;
use App\Models\Deposit;
use App\Models\SpDistribution;
use App\Models\User;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SpAdminFinancialOverviewWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = '60s';

    protected int|array|null $columns = 4;

    public function getStats(): array
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);
        $selectedSpId = $this->pageFilters['sp_id'] ?? null;

        $spQuery = User::withoutGlobalScopes()->sps()->whereNull('sp_id');
        if ($selectedSpId) {
            $spQuery->where('id', $selectedSpId);
        }
        $spIds = $spQuery->pluck('id');

        $customerQuery = User::withoutGlobalScopes()->customers()->whereIn('sp_id', $spIds);
        $customerIds = $customerQuery->pluck('id');

        $totalSpDeposits = (float) Deposit::withoutGlobalScopes()
            ->whereIn('user_id', $spIds)
            ->where('status', DepositStatus::Completed)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->sum('amount');

        $totalDistributed = (float) SpDistribution::query()
            ->whereIn('sp_id', $spIds)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->sum('amount');

        $totalCustomerTopups = (float) Deposit::withoutGlobalScopes()
            ->whereIn('user_id', $customerIds)
            ->where('status', DepositStatus::Completed)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->sum('amount');

        $totalSpBalances = (float) User::withoutGlobalScopes()
            ->whereIn('id', $spIds)
            ->sum('balance');

        return [
            Stat::make('S P Deposits', number_format($totalSpDeposits, 2).' BDT')
                ->description('Direct S P wallet top-ups')
                ->color('info')
                ->icon('heroicon-o-credit-card'),
            Stat::make('Total Distributed', number_format($totalDistributed, 2).' BDT')
                ->description('To customer wallets')
                ->color('primary')
                ->icon('heroicon-o-arrows-right-left'),
            Stat::make('Customer Top-ups', number_format($totalCustomerTopups, 2).' BDT')
                ->description('Top-ups via reseller')
                ->color('success')
                ->icon('heroicon-o-currency-bangladeshi'),
            Stat::make('S P Wallet Holdings', number_format($totalSpBalances, 2).' BDT')
                ->description('Total current S P balances')
                ->color('warning')
                ->icon('heroicon-o-wallet'),
        ];
    }
}
