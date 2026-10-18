<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Gateway;
use App\Models\Message;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LiveGatewayActivityWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.live-gateway-activity';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function getGatewayActivity(): Collection
    {
        $fiveMinutesAgo = now()->subMinutes(5);
        $oneMinuteAgo = now()->subMinutes(1);

        // Get all gateways
        $gateways = Gateway::query()
            ->select('id', 'name', 'driver', 'is_enabled', 'last_health_latency_ms')
            ->get()
            ->keyBy('id');

        if ($gateways->isEmpty()) {
            return collect();
        }

        // Single aggregation query for all gateways (5 min window)
        $stats = Message::withoutGlobalScopes()
            ->whereNotNull('gateway_id')
            ->where('created_at', '>=', $fiveMinutesAgo)
            ->select([
                'gateway_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN ("sent","delivered") THEN 1 ELSE 0 END) as sent'),
                DB::raw('SUM(CASE WHEN status IN ("failed","dlr_failed") THEN 1 ELSE 0 END) as failed'),
                DB::raw('SUM(CASE WHEN status IN ("pending","sending") THEN 1 ELSE 0 END) as pending'),
                DB::raw('SUM(cost) as cost'),
            ])
            ->groupBy('gateway_id')
            ->get()
            ->keyBy('gateway_id');

        // Throughput query (last 1 min)
        $throughput = Message::withoutGlobalScopes()
            ->whereNotNull('gateway_id')
            ->where('created_at', '>=', $oneMinuteAgo)
            ->select('gateway_id', DB::raw('COUNT(*) as count'))
            ->groupBy('gateway_id')
            ->pluck('count', 'gateway_id');

        return $gateways->map(function ($gateway) use ($stats, $throughput) {
            $stat = $stats->get($gateway->id);
            $total = $stat->total ?? 0;
            $sent = $stat->sent ?? 0;

            return [
                'id' => $gateway->id,
                'name' => $gateway->name,
                'driver' => $gateway->driver?->value ?? 'unknown',
                'is_enabled' => $gateway->is_enabled,
                'latency' => $gateway->last_health_latency_ms,
                'total' => $total,
                'sent' => $sent,
                'failed' => $stat->failed ?? 0,
                'pending' => $stat->pending ?? 0,
                'cost' => $stat->cost ?? 0,
                'throughput' => $throughput->get($gateway->id, 0),
                'success_rate' => $total > 0 ? round(($sent / $total) * 100, 1) : null,
                'is_active' => $total > 0,
            ];
        })->sortByDesc('total')->values();
    }
}
