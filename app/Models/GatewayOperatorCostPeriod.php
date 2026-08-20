<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Guarded(['id'])]
class GatewayOperatorCostPeriod extends Model
{
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'manual_cost' => 'float',
            'api_cost' => 'float',
            'cost_variance' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (GatewayOperatorCostPeriod $period): void {
            if ($period->api_cost !== null) {
                $period->cost_variance = round($period->manual_cost - $period->api_cost, 4);
            }

            if ($period->dlr_delivered_count !== null && $period->dlr_api_delivered_count !== null) {
                $period->dlr_variance = $period->dlr_delivered_count - $period->dlr_api_delivered_count;
            }
        });
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(Gateway::class);
    }

    public static function findForPeriod(Gateway $gateway, Carbon $from, Carbon $to): ?self
    {
        return self::query()
            ->where('gateway_id', $gateway->id)
            ->whereDate('period_start', '<=', $from->toDateString())
            ->whereDate('period_end', '>=', $to->toDateString())
            ->first();
    }
}
