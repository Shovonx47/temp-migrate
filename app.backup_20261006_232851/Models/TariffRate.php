<?php

namespace App\Models;

use App\Observers\TariffRateObserver;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Guarded(['id'])]
#[ObservedBy(TariffRateObserver::class)]
class TariffRate extends Model
{
    protected function casts(): array
    {
        return [
            'masking_price' => 'float',
            'non_masking_price' => 'float',
            'effective_from' => 'date',
        ];
    }

    public function tariffPlan(): BelongsTo
    {
        return $this->belongsTo(TariffPlan::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(TariffRateHistory::class);
    }
}
