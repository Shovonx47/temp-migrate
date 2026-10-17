<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class TariffRateHistory extends Model
{
    protected $table = 'tariff_rate_history';

    protected function casts(): array
    {
        return [
            'old_masking_price' => 'float',
            'new_masking_price' => 'float',
            'old_non_masking_price' => 'float',
            'new_non_masking_price' => 'float',
            'changed_at' => 'datetime',
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

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
