<?php

namespace App\Models;

use App\Enums\GatewayCostMode;
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
            'requests_per_second' => 'integer',
            'supports_bulk' => 'boolean',
            'dlr_support' => 'boolean',
            'cost_mode' => GatewayCostMode::class,
            'dipping_required' => 'boolean',
            'has_third_party_dipping' => 'boolean',
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

    public function dippingCharges(): HasMany
    {
        return $this->hasMany(GatewayDippingCharge::class);
    }

    public function operatorCostPeriods(): HasMany
    {
        return $this->hasMany(GatewayOperatorCostPeriod::class);
    }

    public function thirdPartyDippingConfigs(): HasMany
    {
        return $this->hasMany(GatewayThirdPartyDippingConfig::class);
    }

    public function thirdPartyDippingPeriods(): HasMany
    {
        return $this->hasMany(GatewayThirdPartyDippingPeriod::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'gateway_id');
    }

    #[Scope]
    protected function enabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }
}
