<?php

namespace App\Services\Sms;

use App\Exceptions\OperatorRateNotConfiguredException;
use App\Models\Operator;
use App\Models\User;

/**
 * Resolves the per-SMS rate for a given (user, phone, masking) tuple.
 *
 * Lookup cascades:
 *   1. user_operator_rates override for the matched operator + sender type
 *   2. operators.masking_price / non_masking_price (system default)
 *   3. Throws OperatorRateNotConfiguredException — admin must fill it in.
 *
 * Build one instance per batch via {@see forUser()} so the operator catalog
 * and the user's overrides are loaded once and reused for every message.
 */
class OperatorRateResolver
{
    /**
     * @param  array<int, Operator>  $operators  keyed by operator id
     * @param  array<string, int>  $codeIndex  "{countryCode}:{opCode}" => operator id
     * @param  array<int, array{masking_price: ?float, non_masking_price: ?float}>  $overrides  operator id => overrides
     */
    public function __construct(
        private readonly array $operators,
        private readonly array $codeIndex,
        private readonly array $overrides,
    ) {}

    /**
     * Build a resolver pre-loaded with the operator catalog and the given user's
     * overrides. Use this at the start of a batch so per-row lookups are O(1).
     */
    public static function forUser(User $user): self
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

        $overrides = $user
            ->operatorRates()
            ->get(['operator_id', 'masking_price', 'non_masking_price'])
            ->keyBy('operator_id')
            ->map(fn ($r): array => [
                'masking_price' => $r->masking_price,
                'non_masking_price' => $r->non_masking_price,
            ])
            ->all();

        return new self($operators, $codeIndex, $overrides);
    }

    /**
     * Resolve the rate for a single E.164 phone + masking flag.
     *
     * @throws OperatorRateNotConfiguredException when no rate is set anywhere
     *                                            in the cascade for this lookup.
     */
    public function resolve(string $e164Phone, bool $isMasking): float
    {
        $operator = $this->matchOperator($e164Phone);

        if ($operator === null) {
            throw new OperatorRateNotConfiguredException(
                phone: $e164Phone,
                operatorName: null,
                isMasking: $isMasking,
                message: "No operator matches phone {$e164Phone}.",
            );
        }

        $field = $isMasking ? 'masking_price' : 'non_masking_price';

        $override = $this->overrides[$operator->id][$field] ?? null;
        if ($override !== null) {
            return (float) $override;
        }

        $default = $operator->{$field};
        if ($default !== null) {
            return (float) $default;
        }

        throw new OperatorRateNotConfiguredException(
            phone: $e164Phone,
            operatorName: $operator->name,
            isMasking: $isMasking,
        );
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

    /**
     * Return all rates this user could be charged for the given masking type
     * across configured operators — used by form-time affordability checks.
     *
     * @return array<int, float>
     */
    public function ratesForUser(bool $isMasking): array
    {
        $field = $isMasking ? 'masking_price' : 'non_masking_price';
        $rates = [];

        foreach ($this->operators as $operator) {
            $override = $this->overrides[$operator->id][$field] ?? null;
            $value = $override ?? $operator->{$field};

            if ($value !== null) {
                $rates[$operator->id] = (float) $value;
            }
        }

        return $rates;
    }
}
