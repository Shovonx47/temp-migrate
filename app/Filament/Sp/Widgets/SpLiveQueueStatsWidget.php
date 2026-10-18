<?php

namespace App\Filament\Sp\Widgets;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class SpLiveQueueStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '5s';

    protected int|array|null $columns = 4;

    protected static ?int $sort = 1;

    public function getStats(): array
    {
        /** @var User|null $actingUser */
        $actingUser = Filament::auth()->user();
        if (! $actingUser) {
            return $this->getEmptyStats();
        }

        $sp = ($actingUser->type->isSp() && $actingUser->sp_id)
            ? (User::find($actingUser->sp_id) ?? $actingUser)
            : $actingUser;

        $customerIds = $sp->spCustomers()->pluck('id');

        if ($customerIds->isEmpty()) {
            return $this->getEmptyStats();
        }

        $fiveMinutesAgo = now()->subMinutes(5);
        $staleThreshold = now()->subMinutes(8);

        // 5-minute aggregation for live speed & success rate
        $fiveMinStats = DB::table('messages')
            ->whereIn('user_id', $customerIds)
            ->where('created_at', '>=', $fiveMinutesAgo)
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN ("sent","delivered") THEN 1 ELSE 0 END) as success_count'),
            ])
            ->first();

        $totalFiveMin = (int) ($fiveMinStats->total ?? 0);
        $successFiveMin = (int) ($fiveMinStats->success_count ?? 0);
        $msgsPerMinute = $totalFiveMin > 0 ? round($totalFiveMin / 5, 1) : 0.0;
        $liveSuccessRate = $totalFiveMin > 0 ? round(($successFiveMin / $totalFiveMin) * 100, 1) : 100.0;

        // Current in-queue total (pending + sending)
        $inQueueCount = DB::table('messages')
            ->whereIn('user_id', $customerIds)
            ->whereIn('status', ['pending', 'sending'])
            ->count();

        // Stale messages stuck in queue > 8 minutes
        $staleCount = DB::table('messages')
            ->whereIn('user_id', $customerIds)
            ->whereIn('status', ['pending', 'sending'])
            ->where('updated_at', '<=', $staleThreshold)
            ->count();

        // Active bulk batches
        $activeBatches = DB::table('batches')
            ->whereIn('user_id', $customerIds)
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        $queueDescription = $staleCount > 0
            ? "⚠️ {$staleCount} stuck >8m (action required)"
            : "Customers' active messages in transit";

        $queueColor = $staleCount > 0
            ? 'danger'
            : ($inQueueCount > 0 ? 'warning' : 'success');

        return [
            Stat::make('⚡ In Queue Now', number_format($inQueueCount))
                ->description($queueDescription)
                ->color($queueColor)
                ->icon('heroicon-o-bolt'),

            Stat::make('📊 Msgs / Min', number_format($msgsPerMinute, 1))
                ->description('Live 5-min throughput of customers')
                ->color('info')
                ->icon('heroicon-o-arrow-trending-up'),

            Stat::make('🔄 Active Batches', number_format($activeBatches))
                ->description('Bulk SMS campaigns in progress')
                ->color($activeBatches > 0 ? 'primary' : 'gray')
                ->icon('heroicon-o-server-stack'),

            Stat::make('✅ Live Success Rate', $liveSuccessRate.'%')
                ->description("{$successFiveMin}/{$totalFiveMin} delivered (last 5 min)")
                ->color($liveSuccessRate >= 90 ? 'success' : ($liveSuccessRate >= 75 ? 'warning' : 'danger'))
                ->icon('heroicon-o-check-badge'),
        ];
    }

    protected function getEmptyStats(): array
    {
        return [
            Stat::make('⚡ In Queue Now', '0')
                ->description('No active messages in queue')
                ->color('gray')
                ->icon('heroicon-o-bolt'),

            Stat::make('📊 Msgs / Min', '0.0')
                ->description('No traffic in last 5 minutes')
                ->color('gray')
                ->icon('heroicon-o-arrow-trending-up'),

            Stat::make('🔄 Active Batches', '0')
                ->description('No bulk campaigns processing')
                ->color('gray')
                ->icon('heroicon-o-server-stack'),

            Stat::make('✅ Live Success Rate', '100%')
                ->description('0/0 delivered')
                ->color('gray')
                ->icon('heroicon-o-check-badge'),
        ];
    }
}
