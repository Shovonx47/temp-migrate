<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Batch;
use App\Models\Message;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class LiveQueueStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '5s';

    protected int|array|null $columns = 5;

    protected static ?int $sort = 2;

    public function getStats(): array
    {
        $fiveMinutesAgo = now()->subMinutes(5);

        // Single aggregation query for last 5 min messages
        $stats = Message::withoutGlobalScopes()
            ->where('created_at', '>=', $fiveMinutesAgo)
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN ("pending","sending") THEN 1 ELSE 0 END) as active_now'),
                DB::raw('SUM(CASE WHEN status IN ("sent","delivered") THEN 1 ELSE 0 END) as success_count'),
                DB::raw('SUM(cost) as total_cost'),
                DB::raw('COUNT(DISTINCT gateway_id) as gateway_count'),
            ])
            ->first();

        $total = $stats->total ?? 0;
        $activeNow = $stats->active_now ?? 0;
        $successCount = $stats->success_count ?? 0;
        $liveCost = $stats->total_cost ?? 0;
        $activeGateways = $stats->gateway_count ?? 0;
        $messagesPerMinute = $total > 0 ? round($total / 5, 1) : 0;
        $liveSuccessRate = $total > 0 ? round(($successCount / $total) * 100, 1) : 0;

        // Active batches (simple count)
        $activeBatches = Batch::withoutGlobalScopes()
            ->where('status', 'processing')
            ->count();

        return [
            Stat::make('⚡ In Queue Now', number_format($activeNow))
                ->description('Pending & sending')
                ->color($activeNow > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-bolt'),

            Stat::make('📊 Msgs/Min', number_format($messagesPerMinute, 1))
                ->description('5-min average rate')
                ->color('info')
                ->icon('heroicon-o-arrow-trending-up'),

            Stat::make('🔄 Active Batches', number_format($activeBatches))
                ->description('Currently processing')
                ->color($activeBatches > 0 ? 'primary' : 'gray')
                ->icon('heroicon-o-server-stack'),

            Stat::make('✅ Live Success', $liveSuccessRate . '%')
                ->description($successCount . '/' . $total . ' (5 min)')
                ->color($liveSuccessRate >= 90 ? 'success' : ($liveSuccessRate >= 70 ? 'warning' : 'danger'))
                ->icon('heroicon-o-check-badge'),

            Stat::make('🏗️ Active Gateways', $activeGateways)
                ->description(number_format($liveCost, 2) . ' BDT burned')
                ->color($activeGateways > 0 ? 'success' : 'gray')
                ->icon('heroicon-o-signal'),
        ];
    }
}
