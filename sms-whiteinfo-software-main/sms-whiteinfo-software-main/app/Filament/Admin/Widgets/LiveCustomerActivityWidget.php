<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Message;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LiveCustomerActivityWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.live-customer-activity';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 5;

    public function getActiveCustomers(): Collection
    {
        $fiveMinutesAgo = now()->subMinutes(5);

        return Message::withoutGlobalScopes()
            ->select(
                'user_id',
                DB::raw('COUNT(*) as message_count'),
                DB::raw('SUM(CASE WHEN status IN ("sent","delivered") THEN 1 ELSE 0 END) as success_count'),
                DB::raw('SUM(CASE WHEN status IN ("failed","dlr_failed") THEN 1 ELSE 0 END) as failed_count'),
                DB::raw('SUM(CASE WHEN status IN ("pending","sending") THEN 1 ELSE 0 END) as pending_count'),
                DB::raw('SUM(cost) as total_cost'),
                DB::raw('MAX(created_at) as last_activity')
            )
            ->with('user:id,name,email')
            ->where('created_at', '>=', $fiveMinutesAgo)
            ->groupBy('user_id')
            ->orderByDesc('message_count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'user_id' => $item->user_id,
                    'name' => $item->user?->name ?? 'Unknown',
                    'email' => $item->user?->email ?? '',
                    'message_count' => $item->message_count,
                    'success_count' => $item->success_count,
                    'failed_count' => $item->failed_count,
                    'pending_count' => $item->pending_count,
                    'total_cost' => number_format($item->total_cost ?? 0, 2),
                    'last_activity' => $item->last_activity
                        ? \Carbon\Carbon::parse($item->last_activity)->diffForHumans(short: true)
                        : '—',
                ];
            });
    }
}
