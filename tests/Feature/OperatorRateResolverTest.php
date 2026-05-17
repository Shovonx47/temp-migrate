<?php

use App\Exceptions\OperatorRateNotConfiguredException;
use App\Models\Country;
use App\Models\Operator;
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

test('returns the operator default rate when user has no override', function () {
    makeBdWithOperators(maskingDefault: 0.5, nonMaskingDefault: 0.3);
    $user = User::factory()->create();

    $resolver = OperatorRateResolver::forUser($user);

    expect($resolver->resolve('+8801711000001', isMasking: false))->toBe(0.3)
        ->and($resolver->resolve('+8801711000001', isMasking: true))->toBe(0.5);
});

test('user override beats the operator default', function () {
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
