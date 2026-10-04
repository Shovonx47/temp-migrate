<?php

namespace App\Models;

use App\Enums\DippingRateMode;
use App\Observers\GatewayDippingChargeObserver;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Guarded(['id'])]
#[ObservedBy(GatewayDippingChargeObserver::class)]
class GatewayDippingCharge extends Model
{
    protected function casts(): array
    {
        return [
            'masking_rate' => 'float',
            'non_masking_rate' => 'float',
            'rate_mode' => DippingRateMode::class,
            'effective_from' => 'date',
        ];
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(Gateway::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(GatewayDippingChargeHistory::class);
    }

    public function calculateCost(float $ratePerPart, int $parts, float $operatorCostTotal = 0.0): float
    {
        return match ($this->rate_mode) {
            DippingRateMode::PerSms => round($ratePerPart * $parts, 4),
            DippingRateMode::Percentage => round(($ratePerPart / 100) * $operatorCostTotal, 4),
        };
    }
}
