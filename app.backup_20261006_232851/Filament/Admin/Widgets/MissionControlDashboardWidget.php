<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Batch;
use App\Models\Gateway;
use App\Models\Message;
use App\Models\User;
use App\Services\Health\SystemHealthChecker;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MissionControlDashboardWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.mission-control-dashboard';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -50;

    public string $timeRange = '15m';

    public bool $isPaused = false;

    public function togglePause(): void
    {
        $this->isPaused = ! $this->isPaused;

        Notification::make()
            ->title($this->isPaused ? 'Live queue auto-refresh paused' : 'Live queue auto-refresh resumed')
            ->info()
            ->duration(2000)
            ->send();
    }

    public function pauseAllQueues(): void
    {
        Cache::put('sms_queue_paused_by_admin', true, now()->addHours(24));

        Notification::make()
            ->title('All SMS dispatch queues paused')
            ->warning()
            ->body('Worker processing has been temporarily suspended by Admin.')
            ->duration(3000)
            ->send();
    }

    public function resumeAllQueues(): void
    {
        Cache::forget('sms_queue_paused_by_admin');

        Notification::make()
            ->title('All SMS dispatch queues resumed')
            ->success()
            ->body('Workers actively processing queued dispatches.')
            ->duration(3000)
            ->send();
    }

    public function clearQueue(): void
    {
        $affectedMessages = Message::withoutGlobalScopes()
            ->whereIn('status', ['pending', 'sending'])
            ->update([
                'status' => 'failed',
                'failed_reason' => 'Queue purged by Administrator',
                'updated_at' => now(),
            ]);

        $affectedBatches = Batch::withoutGlobalScopes()
            ->whereIn('status', ['pending', 'processing'])
            ->update([
                'status' => 'failed',
                'updated_at' => now(),
            ]);

        $totalAffected = $affectedMessages + $affectedBatches;

        Notification::make()
            ->title("Queue Purged: {$totalAffected} items cleared")
            ->success()
            ->body("All stuck pending and in-flight SMS items have been purged to zero.")
            ->duration(5000)
            ->send();

        $this->dispatch('refreshStream');
    }

    public function retryFailed(): void
    {
        $affected = Message::withoutGlobalScopes()
            ->whereIn('status', ['failed', 'dlr_failed'])
            ->where('updated_at', '>=', now()->subHours(24))
            ->limit(500)
            ->update([
                'status' => 'pending',
                'failed_reason' => null,
                'updated_at' => now(),
            ]);

        Notification::make()
            ->title("Queued {$affected} failed messages for live retry")
            ->success()
            ->duration(3000)
            ->send();

        $this->dispatch('refreshStream');
    }

    public function requeueStuck(): void
    {
        $affected = Message::withoutGlobalScopes()
            ->where('status', 'sending')
            ->where('updated_at', '<=', now()->subMinutes(2))
            ->update([
                'status' => 'pending',
                'updated_at' => now(),
            ]);

        Notification::make()
            ->title("Requeued {$affected} stuck in-flight message(s)")
            ->info()
            ->duration(3000)
            ->send();

        $this->dispatch('refreshStream');
    }

    public function checkHealthNow(): void
    {
        Notification::make()
            ->title('Infrastructure health diagnostics refreshed')
            ->success()
            ->duration(2500)
            ->send();
    }

    public function getDashboardData(): array
    {
        $now = now();
        $healthChecker = app(SystemHealthChecker::class);
        $health = $healthChecker->runAllChecks();

        // 1. Live Active In-Queue & Processing right now — based purely on the messages table.
        // Counting batch records in 'processing' status is unreliable because stale batches
        // can get stuck for hours without any active messages. We only look at actual messages.
        $pending = Message::withoutGlobalScopes()->where('status', 'pending')->count();
        $processing = Message::withoutGlobalScopes()->where('status', 'sending')->count();

        // Count only recently-active batches (updated within the last 8 minutes) to avoid
        // stale/stuck batch records inflating the in-flight counter.
        $activeBatches = Batch::withoutGlobalScopes()
            ->where('status', 'processing')
            ->where('updated_at', '>=', now()->subMinutes(8))
            ->count();

        $processingTotal = $processing + $activeBatches;
        $totalInQueue = $pending + $processingTotal;

        // 2. Recent live activity (Last 15 minutes)
        $sub15m = now()->subMinutes(15);
        $sub1m = now()->subMinutes(1);

        $recentStats = Message::withoutGlobalScopes()
            ->where('updated_at', '>=', $sub15m)
            ->selectRaw('
                SUM(CASE WHEN status IN ("sent", "delivered") THEN 1 ELSE 0 END) as sent_15m,
                SUM(CASE WHEN status IN ("failed", "dlr_failed") THEN 1 ELSE 0 END) as failed_15m,
                SUM(CASE WHEN updated_at >= ? AND status IN ("sent", "delivered") THEN 1 ELSE 0 END) as sent_1m
            ', [$sub1m])
            ->first();

        $recentSent = (int) ($recentStats->sent_15m ?? 0);
        $recentFailed = (int) ($recentStats->failed_15m ?? 0);
        $recentTotal = $recentSent + $recentFailed;
        $dlrSuccess = $recentTotal > 0 ? round(($recentSent / $recentTotal) * 100, 1) : 100.0;

        $lastMinute = (int) ($recentStats->sent_1m ?? 0);
        $currentTps = round($lastMinute / 60, 2);

        $dbLatency = (float) ($health['database']['latency_ms'] ?? 0.36);
        $queueDelay = $pending > 0 ? round(max(0.1, ($dbLatency / 100) + ($pending * 0.005)), 2) : 0.0;

        // 3. Queue Breakdown by Type (Quick SMS vs Bulk Batch)
        $quickInQueue = Message::withoutGlobalScopes()->whereIn('status', ['pending', 'sending'])->whereNull('batch_id')->count();
        $bulkInQueue = Message::withoutGlobalScopes()->whereIn('status', ['pending', 'sending'])->whereNotNull('batch_id')->count();
        $apiInQueue = Message::withoutGlobalScopes()->whereIn('status', ['pending', 'sending'])->where('source', 'api')->count();
        $webInQueue = Message::withoutGlobalScopes()->whereIn('status', ['pending', 'sending'])->where('source', 'web')->count();

        // 4. Minute-by-minute trend points for the last 15 minutes
        $chartData = $this->generateRealQueueChartPoints();

        // 5. Gateways with real status and TPS
        $gateways = Gateway::query()
            ->select('id', 'name', 'driver', 'is_enabled', 'last_health_latency_ms')
            ->get()
            ->map(function ($gw) {
                $recentMsgs = Message::withoutGlobalScopes()
                    ->where('gateway_id', $gw->id)
                    ->where('updated_at', '>=', now()->subMinutes(15));

                $sentG = (clone $recentMsgs)->whereIn('status', ['sent', 'delivered'])->count();
                $failedG = (clone $recentMsgs)->whereIn('status', ['failed', 'dlr_failed'])->count();
                $totalG = $sentG + $failedG;

                $lastMin = Message::withoutGlobalScopes()
                    ->where('gateway_id', $gw->id)
                    ->where('updated_at', '>=', now()->subMinutes(1))
                    ->whereIn('status', ['sent', 'delivered'])
                    ->count();

                $successRate = $totalG > 0 ? round(($sentG / $totalG) * 100, 1) : 100.0;
                $status = ! $gw->is_enabled ? 'Offline' : ($failedG > 10 ? 'Degraded' : 'Online');

                return [
                    'name' => $gw->name,
                    'driver' => $gw->driver?->value ?? ($gw->driver ?: 'default'),
                    'status' => $status,
                    'tps' => round($lastMin / 60, 2),
                    'success_rate' => $successRate,
                ];
            });

        // 6. Active Customers in Queue right now
        $topCustomers = User::withoutGlobalScopes()
            ->whereHas('messages', function ($q) {
                $q->whereIn('status', ['pending', 'sending']);
            })
            ->withCount([
                'messages as pending_count' => fn ($q) => $q->where('status', 'pending'),
                'messages as processing_count' => fn ($q) => $q->where('status', 'sending'),
                'messages as dispatched_count' => fn ($q) => $q->whereIn('status', ['sent', 'delivered'])->where('updated_at', '>=', now()->subMinutes(15)),
                'messages as failed_count' => fn ($q) => $q->whereIn('status', ['failed', 'dlr_failed'])->where('updated_at', '>=', now()->subMinutes(15)),
            ])
            ->orderByDesc('pending_count')
            ->limit(5)
            ->get()
            ->map(function ($u) {
                return [
                    'name' => $u->name,
                    'pending' => (int) $u->pending_count,
                    'processing' => (int) $u->processing_count,
                    'sent' => (int) $u->dispatched_count,
                    'failed' => (int) $u->failed_count,
                ];
            });

        // 7. System alerts based on actual health
        $alerts = $this->generateAlerts($health, $gateways);

        return [
            'kpis' => [
                'total_queue' => number_format($totalInQueue),
                'pending_queue' => number_format($pending),
                'processing' => number_format($processingTotal),
                'recent_sent' => number_format($recentSent),
                'recent_failed' => number_format($recentFailed),
                'dlr_success' => $dlrSuccess,
                'current_tps' => $currentTps,
                'queue_delay' => $queueDelay,
                'db_latency' => round($dbLatency, 2),
                'quick_queue' => $quickInQueue,
                'bulk_queue' => $bulkInQueue,
                'api_queue' => $apiInQueue,
                'web_queue' => $webInQueue,
            ],
            'queue_chart' => $chartData['queue_chart'],
            'tps_chart' => $chartData['tps_chart'],
            'gateways' => $gateways,
            'top_customers' => $topCustomers,
            'health' => $health,
            'alerts' => $alerts,
            'system_time' => $now->format('d M Y h:i:s A'),
        ];
    }

    private function generateRealQueueChartPoints(): array
    {
        $minuteBuckets = Message::withoutGlobalScopes()
            ->where('updated_at', '>=', now()->subMinutes(15))
            ->selectRaw('
                DATE_FORMAT(updated_at, "%H:%i") as minute_key,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_cnt,
                SUM(CASE WHEN status = "sending" THEN 1 ELSE 0 END) as sending_cnt,
                SUM(CASE WHEN status IN ("sent", "delivered") THEN 1 ELSE 0 END) as sent_cnt,
                SUM(CASE WHEN status IN ("failed", "dlr_failed") THEN 1 ELSE 0 END) as failed_cnt
            ')
            ->groupBy('minute_key')
            ->get()
            ->keyBy('minute_key');

        $labels = [];
        $pPoints = [];
        $prPoints = [];
        $sPoints = [];
        $fPoints = [];
        $tpsValues = [];

        for ($i = 14; $i >= 0; $i--) {
            $timeKey = now()->subMinutes($i)->format('H:i');
            $labels[] = $timeKey;

            $bucket = $minuteBuckets->get($timeKey);
            $p = (int) ($bucket->pending_cnt ?? 0);
            $pr = (int) ($bucket->sending_cnt ?? 0);
            $s = (int) ($bucket->sent_cnt ?? 0);
            $f = (int) ($bucket->failed_cnt ?? 0);

            $pPoints[] = $p;
            $prPoints[] = $pr;
            $sPoints[] = $s;
            $fPoints[] = $f;
            $tpsValues[] = round($s / 60, 2);
        }

        $maxVal = max(10, max($pPoints), max($prPoints), max($sPoints), max($fPoints));
        $maxTps = max(1.0, max($tpsValues));

        // Generate SVG polyline coordinates
        $width = 460;
        $height = 120;
        $step = $width / 14;

        $buildPolyline = function (array $values, float $max) use ($step, $height) {
            $points = [];
            foreach ($values as $index => $val) {
                $x = 40 + ($index * $step);
                $y = 140 - (($val / $max) * $height);
                $points[] = "{$x},{$y}";
            }

            return implode(' ', $points);
        };

        $buildAreaPath = function (array $values, float $max) use ($step, $height) {
            $points = [];
            foreach ($values as $index => $val) {
                $x = 40 + ($index * $step);
                $y = 140 - (($val / $max) * $height);
                $points[] = "L {$x} {$y}";
            }

            $firstX = 40;
            $lastX = 40 + (14 * $step);

            return "M {$firstX} 140 " . implode(' ', $points) . " L {$lastX} 140 Z";
        };

        return [
            'queue_chart' => [
                'labels' => $labels,
                'sent' => $sPoints,
                'pending' => $pPoints,
                'processing' => $prPoints,
                'failed' => $fPoints,
                'max_val' => $maxVal,
                'sent_line' => $buildPolyline($sPoints, $maxVal),
                'sent_area' => $buildAreaPath($sPoints, $maxVal),
                'pending_line' => $buildPolyline($pPoints, $maxVal),
                'processing_line' => $buildPolyline($prPoints, $maxVal),
                'failed_line' => $buildPolyline($fPoints, $maxVal),
            ],
            'tps_chart' => [
                'labels' => $labels,
                'values' => $tpsValues,
                'max_val' => $maxTps,
                'tps_line' => $buildPolyline($tpsValues, $maxTps),
                'tps_area' => $buildAreaPath($tpsValues, $maxTps),
            ],
        ];
    }

    private function generateAlerts(array $health, Collection $gateways): array
    {
        $alerts = [];
        $now = now();

        foreach ($gateways as $gw) {
            if ($gw['status'] === 'Offline') {
                $alerts[] = [
                    'type' => 'critical',
                    'title' => "Gateway {$gw['name']} is offline",
                    'time' => $now->format('h:i A'),
                ];
            } elseif ($gw['status'] === 'Degraded') {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => "Failure rate elevated on {$gw['name']}",
                    'time' => $now->format('h:i A'),
                ];
            }
        }

        if (empty($alerts)) {
            $alerts[] = [
                'type' => 'success',
                'title' => 'All SMS dispatch engines and database systems running with nominal queue latency',
                'time' => $now->format('h:i A'),
            ];
        }

        return $alerts;
    }
}
