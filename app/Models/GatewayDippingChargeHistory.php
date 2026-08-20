<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class GatewayDippingChargeHistory extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'old_masking_rate' => 'float',
            'new_masking_rate' => 'float',
            'old_non_masking_rate' => 'float',
            'new_non_masking_rate' => 'float',
            'old_effective_from' => 'date',
            'new_effective_from' => 'date',
            'changed_at' => 'datetime',
        ];
    }

    public function dippingCharge(): BelongsTo
    {
        return $this->belongsTo(GatewayDippingCharge::class, 'gateway_dipping_charge_id');
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(Gateway::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
