<?php

namespace App\Support;

use App\Exceptions\OperatorRateNotConfiguredException;
use App\Models\Operator;
use App\Models\TariffRate;
use App\Models\User;

/**
 * Resolves the per-SMS rate for a given (user, phone, masking) tuple.
 *
 * Lookup cascade (in order):
 *   1. Customer's active TariffPlanAssignment → TariffPlan → tariff_rates row for operator
 *   2. user_operator_rates override (legacy fallback — kept during migration window)
 *   3. operators.masking_price / non_masking_price (system default)
 *   4. Throws OperatorRateNotConfiguredException — admin must configure pricing.
 *
 * Build one instance per batch via {@see forUser()} so the operator catalog
 * and the user's rates are loaded once and reused for every message.
 */
readonly class OperatorRateResolver
{
    /**
     * @param  array<int, Operator>  $operators  keyed by operator id
     * @param  array<string, int>  $codeIndex  "{countryCode}:{opCode}" => operator id
     * @param  array<int, array{masking_price: ?float, non_masking_price: ?float}>  $overrides  operator id => rates
     */
    public function __construct(
        private array $operators,
        private array $codeIndex,
        private array $overrides,
        private bool $hasActivePlan = false,
        private ?string $planName = null,
    ) {}

    /**
     * Build a resolver pre-loaded with the operator catalog and the given user's
     * effective rates. Resolves through the tariff plan system first. When an active
     * tariff plan is assigned, lookups strictly respect that plan (no default operator fallback).
     *
     * Use this at the start of a batch so per-row lookups are O(1).
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

        // 1. Try to resolve rates from the user's active tariff plan assignment.
        $planData = self::resolveFromTariffPlan($user);

        if ($planData !== null) {
            return new self(
                $operators,
                $codeIndex,
                $planData['rates'],
                hasActivePlan: true,
                planName: $planData['name'],
            );
        }

        // 2. Fall back to the legacy user_operator_rates table.
        $legacyOverrides = self::resolveFromLegacyOverrides($user);

        return new self($operators, $codeIndex, $legacyOverrides ?? []);
    }

    public function hasActivePlan(): bool
    {
        return $this->hasActivePlan;
    }

    public function getPlanName(): ?string
    {
        return $this->planName;
    }

    /**
     * Load rates from the user's active tariff plan assignment.
     *
     * @return array{name: string, rates: array<int, array{masking_price: ?float, non_masking_price: ?float}>}|null
     *                                                                                                              Returns null when no active assignment exists.
     */
    private static function resolveFromTariffPlan(User $user): ?array
    {
        // Eager-load assignment → plan in one query
        $assignment = $user->activeTariffAssignment()->with('tariffPlan')->first();

        if (! $assignment || ! $assignment->tariffPlan) {
            return null;
        }

        $plan = $assignment->tariffPlan;

        // Load all rate rows for this plan, keyed by operator_id
        $rates = TariffRate::query()
            ->where('tariff_plan_id', $plan->id)
            ->get(['operator_id', 'masking_price', 'non_masking_price'])
            ->keyBy('operator_id')
            ->map(fn ($r): array => [
                'masking_price' => $r->masking_price,
                'non_masking_price' => $r->non_masking_price,
            ])
            ->all();

        return [
            'name' => (string) $plan->name,
            'rates' => $rates,
        ];
    }

    /**
     * Load rates from the legacy user_operator_rates table (backward compat).
     *
     * @return array<int, array{masking_price: ?float, non_masking_price: ?float}>|null
     *                                                                                  Returns null when no legacy overrides exist.
     */
    private static function resolveFromLegacyOverrides(User $user): ?array
    {
        $rows = $user
            ->operatorRates()
            ->get(['operator_id', 'masking_price', 'non_masking_price']);

        if ($rows->isEmpty()) {
            return null;
        }

        return $rows
            ->keyBy('operator_id')
            ->map(fn ($r): array => [
                'masking_price' => $r->masking_price,
                'non_masking_price' => $r->non_masking_price,
            ])
            ->all();
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
        $typeName = $isMasking ? 'masking' : 'non-masking';

        // When a user has an active tariff plan, strictly resolve from that plan.
        if ($this->hasActivePlan) {
            $rate = $this->overrides[$operator->id][$field] ?? null;

            if ($rate !== null) {
                return (float) $rate;
            }

            $planDesc = $this->planName ? "assigned tariff plan [{$this->planName}]" : 'assigned tariff plan';

            throw new OperatorRateNotConfiguredException(
                phone: $e164Phone,
                operatorName: $operator->name,
                isMasking: $isMasking,
                message: "No {$typeName} rate configured in {$planDesc} for operator [{$operator->name}].",
            );
        }

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
            if ($this->hasActivePlan) {
                $override = $this->overrides[$operator->id][$field] ?? null;
                if ($override !== null) {
                    $rates[$operator->id] = (float) $override;
                }
            } else {
                $override = $this->overrides[$operator->id][$field] ?? null;
                $value = $override ?? $operator->{$field};

                if ($value !== null) {
                    $rates[$operator->id] = (float) $value;
                }
            }
        }

        return $rates;
    }
}
