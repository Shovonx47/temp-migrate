<?php

use App\Exceptions\OperatorRateNotConfiguredException;
use App\Models\Country;
use App\Models\Operator;
use App\Models\TariffPlan;
use App\Models\TariffPlanAssignment;
use App\Models\TariffRate;
use App\Models\User;
use App\Support\OperatorRateResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeBdWithOperators(float $maskingDefault = 0.5, float $nonMaskingDefault = 0.3): Country
{
    $bd = Country::create([
        'name' => 'Bangladesh',
        'code' => '880',
        'iso_alpha2' => 'BD',
        'is_active' => true,
    ]);

    Operator::create([
        'country_id' => $bd->id,
        'name' => 'Grameenphone',
        'codes' => [13, 17],
        'masking_price' => $maskingDefault,
        'non_masking_price' => $nonMaskingDefault,
        'is_active' => true,
    ]);
    Operator::create([
        'country_id' => $bd->id,
        'name' => 'Robi',
        'codes' => [18],
        'masking_price' => $maskingDefault,
        'non_masking_price' => $nonMaskingDefault,
        'is_active' => true,
    ]);

    return $bd;
}

// ────────────────────────────────────────────────────────────────────────────
// Original cascade tests (operator default + legacy override) — unchanged
// ────────────────────────────────────────────────────────────────────────────

test('returns the operator default rate when user has no override', function () {
    makeBdWithOperators(maskingDefault: 0.5, nonMaskingDefault: 0.3);
    $user = User::factory()->create();

    $resolver = OperatorRateResolver::forUser($user);

    expect($resolver->resolve('+8801711000001', isMasking: false))->toBe(0.3)
        ->and($resolver->resolve('+8801711000001', isMasking: true))->toBe(0.5);
});

test('legacy user_operator_rates override beats the operator default', function () {
    makeBdWithOperators();
    $gp = Operator::where('name', 'Grameenphone')->first();
    $user = User::factory()->create();
    $user->operatorRates()->create([
        'operator_id' => $gp->id,
        'masking_price' => 0.40,
        'non_masking_price' => 0.20,
    ]);

    $resolver = OperatorRateResolver::forUser($user);

    expect($resolver->resolve('+8801711000001', isMasking: false))->toBe(0.20)
        ->and($resolver->resolve('+8801711000001', isMasking: true))->toBe(0.40);
});

test('a null override field falls through to the operator default', function () {
    makeBdWithOperators(maskingDefault: 0.5, nonMaskingDefault: 0.3);
    $gp = Operator::where('name', 'Grameenphone')->first();
    $user = User::factory()->create();
    $user->operatorRates()->create([
        'operator_id' => $gp->id,
        'masking_price' => 0.40,
        'non_masking_price' => null,
    ]);

    $resolver = OperatorRateResolver::forUser($user);

    expect($resolver->resolve('+8801711000001', isMasking: false))->toBe(0.3)
        ->and($resolver->resolve('+8801711000001', isMasking: true))->toBe(0.40);
});

test('different operator codes pick different operators', function () {
    makeBdWithOperators();
    Operator::where('name', 'Robi')->update(['non_masking_price' => 0.99]);
    $user = User::factory()->create();

    $resolver = OperatorRateResolver::forUser($user);

    expect($resolver->resolve('+8801711000001', isMasking: false))->toBe(0.3)
        ->and($resolver->resolve('+8801811000001', isMasking: false))->toBe(0.99);
});

test('throws when no operator matches the phone', function () {
    makeBdWithOperators();
    $user = User::factory()->create();

    $resolver = OperatorRateResolver::forUser($user);

    expect(fn () => $resolver->resolve('+9991234567890', isMasking: false))
        ->toThrow(OperatorRateNotConfiguredException::class);
});

test('throws when operator default and user override are both null for the requested type', function () {
    makeBdWithOperators();
    Operator::where('name', 'Grameenphone')->update(['masking_price' => null]);
    $user = User::factory()->create();

    $resolver = OperatorRateResolver::forUser($user);

    expect(fn () => $resolver->resolve('+8801711000001', isMasking: true))
        ->toThrow(OperatorRateNotConfiguredException::class);
});

test('ratesForUser returns all configured per-type rates for affordability hints', function () {
    makeBdWithOperators(maskingDefault: 0.5, nonMaskingDefault: 0.3);
    $gp = Operator::where('name', 'Grameenphone')->first();
    $user = User::factory()->create();
    $user->operatorRates()->create([
        'operator_id' => $gp->id,
        'masking_price' => 0.42,
        'non_masking_price' => null,
    ]);

    $resolver = OperatorRateResolver::forUser($user);

    expect(min($resolver->ratesForUser(true)))->toBe(0.42)
        ->and(max($resolver->ratesForUser(true)))->toBe(0.5)
        ->and($resolver->ratesForUser(false))->toBe([
            $gp->id => 0.3,
            Operator::where('name', 'Robi')->first()->id => 0.3,
        ]);
});

// ────────────────────────────────────────────────────────────────────────────
// NEW: Tariff Plan cascade tests
// ────────────────────────────────────────────────────────────────────────────

test('tariff plan rates beat operator defaults', function () {
    makeBdWithOperators(maskingDefault: 0.5, nonMaskingDefault: 0.3);
    $gp = Operator::where('name', 'Grameenphone')->first();

    $user = User::factory()->create();

    $plan = TariffPlan::create([
        'name' => 'Test Plan',
        'type' => 'standard',
        'status' => 'active',
        'currency' => 'BDT',
    ]);

    TariffRate::create([
        'tariff_plan_id' => $plan->id,
        'operator_id' => $gp->id,
        'masking_price' => 0.35,
        'non_masking_price' => 0.25,
    ]);

    TariffPlanAssignment::create([
        'user_id' => $user->id,
        'tariff_plan_id' => $plan->id,
        'assigned_by' => null,
        'effective_from' => today(),
        'status' => 'active',
    ]);

    $resolver = OperatorRateResolver::forUser($user);

    expect($resolver->resolve('+8801711000001', isMasking: true))->toBe(0.35)
        ->and($resolver->resolve('+8801711000001', isMasking: false))->toBe(0.25);
});

test('tariff plan beats legacy user_operator_rates override', function () {
    makeBdWithOperators(maskingDefault: 0.5, nonMaskingDefault: 0.3);
    $gp = Operator::where('name', 'Grameenphone')->first();

    $user = User::factory()->create();

    // Legacy override that should be ignored once a plan is assigned
    $user->operatorRates()->create([
        'operator_id' => $gp->id,
        'masking_price' => 0.40,
        'non_masking_price' => 0.20,
    ]);

    $plan = TariffPlan::create([
        'name' => 'Priority Plan',
        'type' => 'standard',
        'status' => 'active',
        'currency' => 'BDT',
    ]);

    TariffRate::create([
        'tariff_plan_id' => $plan->id,
        'operator_id' => $gp->id,
        'masking_price' => 0.35,
        'non_masking_price' => 0.22,
    ]);

    TariffPlanAssignment::create([
        'user_id' => $user->id,
        'tariff_plan_id' => $plan->id,
        'assigned_by' => null,
        'effective_from' => today(),
        'status' => 'active',
    ]);

    $resolver = OperatorRateResolver::forUser($user);

    // Tariff plan wins
    expect($resolver->resolve('+8801711000001', isMasking: true))->toBe(0.35)
        ->and($resolver->resolve('+8801711000001', isMasking: false))->toBe(0.22);
});

test('superseded assignment is ignored; only active assignment is used', function () {
    makeBdWithOperators(maskingDefault: 0.5, nonMaskingDefault: 0.3);
    $gp = Operator::where('name', 'Grameenphone')->first();

    $user = User::factory()->create();

    $oldPlan = TariffPlan::create([
        'name' => 'Old Plan',
        'type' => 'custom',
        'status' => 'active',
        'currency' => 'BDT',
    ]);
    TariffRate::create([
        'tariff_plan_id' => $oldPlan->id,
        'operator_id' => $gp->id,
        'masking_price' => 0.99,
        'non_masking_price' => 0.99,
    ]);
    TariffPlanAssignment::create([
        'user_id' => $user->id,
        'tariff_plan_id' => $oldPlan->id,
        'effective_from' => today()->subDays(30),
        'status' => 'superseded',
    ]);

    $newPlan = TariffPlan::create([
        'name' => 'New Plan',
        'type' => 'standard',
        'status' => 'active',
        'currency' => 'BDT',
    ]);
    TariffRate::create([
        'tariff_plan_id' => $newPlan->id,
        'operator_id' => $gp->id,
        'masking_price' => 0.35,
        'non_masking_price' => 0.22,
    ]);
    TariffPlanAssignment::create([
        'user_id' => $user->id,
        'tariff_plan_id' => $newPlan->id,
        'effective_from' => today(),
        'status' => 'active',
    ]);

    $resolver = OperatorRateResolver::forUser($user);

    // Only the active (new) plan should apply
    expect($resolver->resolve('+8801711000001', isMasking: true))->toBe(0.35);
});

test('user with no plan assignment falls back to legacy operator rates', function () {
    makeBdWithOperators(maskingDefault: 0.5, nonMaskingDefault: 0.3);
    $gp = Operator::where('name', 'Grameenphone')->first();

    $user = User::factory()->create();
    $user->operatorRates()->create([
        'operator_id' => $gp->id,
        'masking_price' => 0.40,
        'non_masking_price' => 0.20,
    ]);

    // No plan assignment
    $resolver = OperatorRateResolver::forUser($user);

    expect($resolver->resolve('+8801711000001', isMasking: true))->toBe(0.40)
        ->and($resolver->resolve('+8801711000001', isMasking: false))->toBe(0.20);
});

test('user with no plan and no legacy rates falls back to operator system default', function () {
    makeBdWithOperators(maskingDefault: 0.5, nonMaskingDefault: 0.3);

    $user = User::factory()->create();
    // No plan, no legacy overrides

    $resolver = OperatorRateResolver::forUser($user);

    expect($resolver->resolve('+8801711000001', isMasking: true))->toBe(0.5)
        ->and($resolver->resolve('+8801711000001', isMasking: false))->toBe(0.3);
});
