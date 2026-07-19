<?php

namespace App\Filament\Customer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class CampaignStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    public function getStats(): array
    {
        $stats = auth()->user()->batches()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END), 0) as processing")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as completed")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending")
            ->first();

        return [
            Stat::make('Total Campaigns', Number::abbreviate((int) $stats->total))
                ->description('All campaigns')
                ->icon('heroicon-o-megaphone'),

            Stat::make('Processing', Number::abbreviate((int) $stats->processing))
                ->description('Active campaigns')
                ->color('success')
                ->icon('heroicon-o-play'),

            Stat::make('Completed', Number::abbreviate((int) $stats->completed))
                ->description('Finished campaigns')
                ->color('primary')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Pending', Number::abbreviate((int) $stats->pending))
                ->description('Awaiting approval')
                ->color('warning')
                ->icon('heroicon-o-clock'),
        ];
    }
}
