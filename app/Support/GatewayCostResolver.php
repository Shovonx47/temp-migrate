<?php

namespace App\Support;

use App\Models\Gateway;
use App\Models\Operator;

/**
 * Resolves the gateway buying cost for a given (gateway, phone, masking) tuple.
 *
 * Looks up the gateway_operator_rates for the given gateway, then matches the
 * phone number to an operator via country code + prefix lookup.
 *
 * Falls back to 0 when no rate is configured (unlike OperatorRateResolver which
 * throws, since gateway cost is informational and doesn't block sending).
 */
readonly class GatewayCostResolver
{
    /**
     * @param  array<int, Operator>  $operators  keyed by operator id
     * @param  array<string, int>  $codeIndex  "{countryCode}:{opCode}" => operator id
     * @param  array<int, array{masking_price: ?float, non_masking_price: ?float}>  $rates  operator id => rates
     */
    public function __construct(
        private array $operators,
        private array $codeIndex,
        private array $rates,
    ) {}

    /**
     * Build a resolver pre-loaded with the gateway's operator rates.
     */
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

        $rates = $gateway
            ->gatewayOperatorRates()
            ->get(['operator_id', 'masking_price', 'non_masking_price'])
            ->keyBy('operator_id')
            ->map(fn ($r): array => [
                'masking_price' => $r->masking_price,
                'non_masking_price' => $r->non_masking_price,
            ])
            ->all();

        return new self($operators, $codeIndex, $rates);
    }

    /**
     * Resolve the gateway cost per SMS part for a phone + masking flag.
     * Returns 0 when no rate is configured.
     */
    public function resolve(string $e164Phone, bool $isMasking): float
    {
        $operator = $this->matchOperator($e164Phone);

        if ($operator === null) {
            return 0.0;
        }

        $field = $isMasking ? 'masking_price' : 'non_masking_price';

        $rate = $this->rates[$operator->id][$field] ?? null;

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
