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
            'timeout_seconds' => 'integer',
            'supports_bulk' => 'boolean',
            'dlr_support' => 'boolean',
            'cost_mode' => GatewayCostMode::class,
            'dipping_required' => 'boolean',
            'has_third_party_dipping' => 'boolean',
            'priority' => 'integer',
            'is_otp_enabled' => 'boolean',
            'is_enabled' => 'boolean',
            'last_health_check_at' => 'datetime',
            'last_health_latency_ms' => 'integer',
            'last_health_response' => 'array',
        ];
    }

    public function routePlanGateways(): HasMany
    {
        return $this->hasMany(RoutePlanGateway::class);
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

    public function smsApiType(): string
    {
        return strtolower(trim((string) data_get($this->credentials, 'api_type', 'iptsp'))) === 'mno'
            ? 'mno'
            : 'iptsp';
    }

    public function usesMaskingRates(): bool
    {
        return $this->smsApiType() === 'mno';
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

    #[Scope]
    protected function forOtp(Builder $query): Builder
    {
        return $query->where('is_enabled', true)->where('is_otp_enabled', true);
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('priority', 'asc')->orderBy('id', 'asc');
    }
}
