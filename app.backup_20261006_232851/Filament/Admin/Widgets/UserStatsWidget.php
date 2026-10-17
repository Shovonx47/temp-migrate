<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected int|array|null $columns = 4;

    public function getStats(): array
    {
        $newUsers30d = User::withoutGlobalScopes()
            ->customers()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $pendingApprovals = User::withoutGlobalScopes()
            ->customers()
            ->where('status', 'pending')
            ->count();

        $apiAccessUsers = User::withoutGlobalScopes()
            ->customers()
            ->where('api_access', true)
            ->count();

        $totalBalance = User::withoutGlobalScopes()
            ->customers()
            ->sum('balance');

        return [
            Stat::make('New Users (30d)', number_format($newUsers30d))
                ->description('Recent registrations')
                ->icon('heroicon-o-user-plus'),

            Stat::make('Pending Approvals', number_format($pendingApprovals))
                ->description('Awaiting review')
                ->color('warning')
                ->icon('heroicon-o-clock'),

            Stat::make('API Users', number_format($apiAccessUsers))
                ->description('With API access')
                ->color('info')
                ->icon('heroicon-o-key'),

            Stat::make('Total Balance', number_format($totalBalance, 2).' BDT')
                ->description('Across all users')
                ->color('primary')
                ->icon('heroicon-o-building-library'),
        ];
    }
}
