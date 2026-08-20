<?php

namespace App\Services\Billing;

use App\Models\Gateway;
use App\Models\GatewayDippingCharge;
use App\Models\Operator;
use Illuminate\Support\Carbon;

readonly class DippingChargeResolver
{
    /**
     * @param  array<int, Operator>  $operators
     * @param  array<string, int>  $codeIndex
     * @param  array<int, GatewayDippingCharge>  $chargesByOperator
     */
    public function __construct(
        private array $operators,
        private array $codeIndex,
        private array $chargesByOperator,
        private ?GatewayDippingCharge $gatewayWideCharge,
    ) {}

    public static function forGateway(Gateway $gateway, ?Carbon $asOf = null): self
    {
        $asOf = ($asOf ?? now())->startOfDay();

        $operators = Operator::query()
            ->active()
            ->with(['country' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->keyBy('id')
            ->all();

        $codeIndex = [];
        foreach ($operators as $operator) {
            if (! $operator->country) {
                continue;
            }
            $countryCode = (string) $operator->country->code;
            foreach ((array) $operator->codes as $code) {
                $codeIndex[$countryCode.':'.(string) $code] = $operator->id;
            }
        }

        $charges = GatewayDippingCharge::query()
            ->where('gateway_id', $gateway->id)
            ->where(function ($query) use ($asOf): void {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $asOf->toDateString());
            })
            ->orderByDesc('effective_from')
            ->get();

        $chargesByOperator = [];
        $gatewayWideCharge = null;

        foreach ($charges as $charge) {
            if ($charge->operator_id === null) {
                if ($gatewayWideCharge === null) {
                    $gatewayWideCharge = $charge;
                }

                continue;
            }

            if (! isset($chargesByOperator[$charge->operator_id])) {
                $chargesByOperator[$charge->operator_id] = $charge;
            }
        }

        return new self($operators, $codeIndex, $chargesByOperator, $gatewayWideCharge);
    }

    public static function gatewayHasConfiguration(Gateway $gateway, ?Carbon $asOf = null): bool
    {
        $resolver = self::forGateway($gateway, $asOf);

        return $resolver->gatewayWideCharge !== null || $resolver->chargesByOperator !== [];
    }

    /**
     * @return array{charge: GatewayDippingCharge|null, rate: float}
     */
    public function resolve(string $e164Phone, bool $isMasking, float $operatorCostTotal = 0.0, int $parts = 1): array
    {
        $charge = $this->resolveCharge($e164Phone);

        if ($charge === null) {
            return ['charge' => null, 'rate' => 0.0, 'cost' => 0.0];
        }

        $field = $isMasking ? 'masking_rate' : 'non_masking_rate';
        $rate = (float) ($charge->{$field} ?? 0.0);

        return [
            'charge' => $charge,
            'rate' => $rate,
            'cost' => $charge->calculateCost($rate, $parts, $operatorCostTotal),
        ];
    }

    public function resolveCharge(string $e164Phone): ?GatewayDippingCharge
    {
        $operator = $this->matchOperator($e164Phone);

        if ($operator === null) {
            return $this->gatewayWideCharge;
        }

        return $this->chargesByOperator[$operator->id] ?? $this->gatewayWideCharge;
    }

    private function matchOperator(string $e164Phone): ?Operator
    {
        $digits = ltrim($e164Phone, '+');

        foreach ($this->codeIndex as $key => $operatorId) {
            [$country, $code] = explode(':', $key, 2);

            if (! str_starts_with($digits, $country)) {
                continue;
            }

            $remainder = substr($digits, strlen($country));
            if (str_starts_with($remainder, $code)) {
                return $this->operators[$operatorId] ?? null;
            }
        }

        return null;
    }
}
