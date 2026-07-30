<?php

namespace App\Models;

use App\Enums\GatewayDriver;
use Database\Factories\GatewayFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Guarded(['id'])]
#[Hidden(['credentials', 'config'])]
class Gateway extends Model
{
    /** @use HasFactory<GatewayFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'driver' => GatewayDriver::class,
            'credentials' => 'encrypted:array',
            'config' => 'array',
            'requests_per_minute' => 'integer',
            'supports_bulk' => 'boolean',
        ];
    }

    public function senders(): HasMany
    {
        return $this->hasMany(Sender::class, 'gateway_id');
    }

    public function gatewayOperatorRates(): HasMany
    {
        return $this->hasMany(GatewayOperatorRate::class);
    }

    #[Scope]
    protected function enabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }
}
