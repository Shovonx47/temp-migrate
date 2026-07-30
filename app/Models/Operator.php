<?php

namespace App\Models;

use Database\Factories\OperatorFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Guarded(['id'])]
class Operator extends Model
{
    /** @use HasFactory<OperatorFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'codes' => 'array',
            'masking_price' => 'float',
            'non_masking_price' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function userRates(): HasMany
    {
        return $this->hasMany(UserOperatorRate::class);
    }

    public function gatewayOperatorRates(): HasMany
    {
        return $this->hasMany(GatewayOperatorRate::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
