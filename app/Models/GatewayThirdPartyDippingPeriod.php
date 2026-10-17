<?php

namespace App\Models;

use App\Enums\DippingRateMode;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Guarded(['id'])]
class GatewayThirdPartyDippingPeriod extends Model
{
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'manual_cost' => 'float',
            'rate' => 'float',
            'rate_mode' => DippingRateMode::class,
        ];
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
