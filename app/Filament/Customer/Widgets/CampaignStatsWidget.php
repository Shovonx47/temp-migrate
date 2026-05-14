<?php

namespace App\Filament\Customer\Widgets;

use App\Models\Campaign;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CampaignStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    public function getStats(): array
    {
        $userId = auth()->id();

        return [
            Stat::make('Total Campaigns', Campaign::where('user_id', $userId)->count())
                ->description('All campaigns')
                ->icon('heroicon-o-megaphone'),

            Stat::make('Running', Campaign::where('user_id', $userId)->where('status', 'running')->count())
                ->description('Active campaigns')
                ->color('success')
                ->icon('heroicon-o-play'),

            Stat::make('Completed', Campaign::where('user_id', $userId)->where('status', 'completed')->count())
                ->description('Finished campaigns')
                ->color('primary')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Scheduled', Campaign::where('user_id', $userId)->where('status', 'scheduled')->count())
                ->description('Upcoming campaigns')
                ->color('warning')
                ->icon('heroicon-o-clock'),
        ];
    }
}
