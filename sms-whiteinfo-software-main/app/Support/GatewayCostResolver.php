<?php

namespace App\Support;

use App\Models\Gateway;
use App\Models\Operator;
use App\Services\Billing\DippingChargeResolver;

/**
 * Resolves gateway buying costs for a given (gateway, phone, masking) tuple.
 *
 * Primary dipping costs are resolved via {@see DippingChargeResolver}.
 * Operator base costs come from the operators table.
 */
readonly class GatewayCostResolver
{
    /**
     * @param  array<int, Operator>  $operators  keyed by operator id
     * @param  array<string, int>  $codeIndex  "{countryCode}:{opCode}" => operator id
     */
    public function __construct(
        private array $operators,
        private array $codeIndex,
        private DippingChargeResolver $dippingChargeResolver,
    ) {}

    public static function forGateway(Gateway $gateway): self
    {
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

        return new self(
            $operators,
            $codeIndex,
            DippingChargeResolver::forGateway($gateway),
        );
    }

    public function resolve(string $e164Phone, bool $isMasking): float
    {
        return $this->resolveGatewayCost($e164Phone, $isMasking);
    }

    public function resolveGatewayCost(string $e164Phone, bool $isMasking): float
    {
        return $this->dippingChargeResolver->resolve($e164Phone, $isMasking)['rate'];
    }

    public function resolveOperatorCost(string $e164Phone, bool $isMasking): float
    {
        $operator = $this->matchOperator($e164Phone);

        if ($operator === null) {
            return 0.0;
        }

        $field = $isMasking ? 'masking_price' : 'non_masking_price';

        $rate = $operator->{$field} ?? null;

        return $rate !== null ? (float) $rate : 0.0;
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
