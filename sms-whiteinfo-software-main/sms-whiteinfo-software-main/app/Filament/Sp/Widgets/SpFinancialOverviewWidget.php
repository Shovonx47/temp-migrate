<?php

namespace App\Filament\Sp\Widgets;

use App\Enums\DepositStatus;
use App\Filament\Concerns\HasDateFilter;
use App\Models\Deposit;
use App\Models\SpDistribution;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class SpFinancialOverviewWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = '60s';

    protected int|array|null $columns = 4;

    public function getStats(): array
    {
        /** @var User $actingUser */
        $actingUser = Filament::auth()->user();
        $sp = ($actingUser->type->isSp() && $actingUser->sp_id) ? (User::find($actingUser->sp_id) ?? $actingUser) : $actingUser;

        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $customerIds = $sp->spCustomers()->pluck('id');

        $totalDistributed = (float) SpDistribution::query()
            ->where('sp_id', $sp->id)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->sum('amount');

        $customerDeposits = (float) Deposit::withoutGlobalScopes()
            ->whereIn('user_id', $customerIds)
            ->where('status', DepositStatus::Completed)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->sum('amount');

        $spDeposits = (float) Deposit::withoutGlobalScopes()
            ->where('user_id', $sp->id)
            ->where('status', DepositStatus::Completed)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->sum('amount');

        $activeCustomers = DB::table('messages')
            ->whereIn('user_id', $customerIds)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->distinct('user_id')
            ->count('user_id');

        return [
            Stat::make('Balance Distributed', number_format($totalDistributed, 2).' BDT')
                ->description('Transferred to customers')
                ->color('primary')
                ->icon('heroicon-o-arrows-right-left'),
            Stat::make('Customer Top-ups', number_format($customerDeposits, 2).' BDT')
                ->description('Deposited by your customers')
                ->color('success')
                ->icon('heroicon-o-currency-bangladeshi'),
            Stat::make('S P Wallet Deposits', number_format($spDeposits, 2).' BDT')
                ->description('Added to your S P wallet')
                ->color('info')
                ->icon('heroicon-o-credit-card'),
            Stat::make('Active Customers', number_format($activeCustomers))
                ->description('Sent messages in period')
                ->color('success')
                ->icon('heroicon-o-users'),
        ];
    }
}
