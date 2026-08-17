<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class GatewayOperatorRate extends Model
{
    protected function casts(): array
    {
        return [
            'masking_price' => 'float',
            'non_masking_price' => 'float',
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

    public function getOperatorSpentAttribute(): float
    {
        $operator = $this->operator;
        if (! $operator || empty($operator->codes) || ! $operator->country) {
            return 0.0;
        }

        $countryCode = (string) $operator->country->code;
        $codes = (array) $operator->codes;

        return (float) Message::where('gateway_id', $this->gateway_id)
            ->where('status', '!=', 'failed')
            ->where(function ($q) use ($countryCode, $codes) {
                foreach ($codes as $code) {
                    $prefix = '+'.$countryCode.$code;
                    $q->orWhere('to', 'like', $prefix.'%');
                }
            })
            ->sum('operator_cost');
    }

    public function getGatewaySpentAttribute(): float
    {
        $operator = $this->operator;
        if (! $operator || empty($operator->codes) || ! $operator->country) {
            return 0.0;
        }

        $countryCode = (string) $operator->country->code;
        $codes = (array) $operator->codes;

        return (float) Message::where('gateway_id', $this->gateway_id)
            ->where('status', '!=', 'failed')
            ->where(function ($q) use ($countryCode, $codes) {
                foreach ($codes as $code) {
                    $prefix = '+'.$countryCode.$code;
                    $q->orWhere('to', 'like', $prefix.'%');
                }
            })
            ->sum('gateway_cost');
    }
}
