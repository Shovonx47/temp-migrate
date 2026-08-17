<?php

namespace App\Filament\Sp\Widgets;

use App\Filament\Concerns\HasDateFilter;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class SpDeliveryStatsWidget extends BaseWidget
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

        $stats = DB::table('messages')
            ->whereIn('user_id', $customerIds)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = \'delivered\' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = \'failed\' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status IN (\'pending\', \'sending\', \'sent\') THEN 1 ELSE 0 END) as in_progress
            ')
            ->first();

        $total = (int) ($stats?->total ?? 0);
        $delivered = (int) ($stats?->delivered ?? 0);
        $failed = (int) ($stats?->failed ?? 0);
        $inProgress = (int) ($stats?->in_progress ?? 0);

        $deliveryRate = $total > 0 ? round(($delivered / $total) * 100, 1) : 0.0;

        return [
            Stat::make('Delivery Rate', $deliveryRate.'%')
                ->description($delivered.' of '.$total.' delivered')
                ->color($deliveryRate >= 85 ? 'success' : ($deliveryRate >= 50 ? 'warning' : 'danger'))
                ->icon('heroicon-o-check-badge'),
            Stat::make('Delivered', number_format($delivered))
                ->description('Successfully delivered')
                ->color('success')
                ->icon('heroicon-o-check-circle'),
            Stat::make('Failed', number_format($failed))
                ->description('Undelivered / rejected')
                ->color($failed > 0 ? 'danger' : 'gray')
                ->icon('heroicon-o-x-circle'),
            Stat::make('In Transit / Pending', number_format($inProgress))
                ->description('Currently processing')
                ->color('primary')
                ->icon('heroicon-o-clock'),
        ];
    }
}
