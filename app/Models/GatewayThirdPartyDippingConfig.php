<?php

namespace App\Models;

use App\Enums\DippingRateMode;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class GatewayThirdPartyDippingConfig extends Model
{
    protected function casts(): array
    {
        return [
            'rate' => 'float',
            'rate_mode' => DippingRateMode::class,
            'effective_from' => 'date',
        ];
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(Gateway::class);
    }
}
