<?php

use App\Models\TariffPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

test('scoped by sp filters customers when acting as an sp', function () {
    $spA = User::factory()->sp()->create();
    $spB = User::factory()->sp()->create();
    $customerA = User::factory()->customerOf($spA)->create();
    $customerB = User::factory()->customerOf($spB)->create();

    Auth::guard('sp')->login($spA);

    $visibleIds = User::query()->pluck('id')->all();

    expect($visibleIds)->toContain($spA->id, $customerA->id)
        ->and($visibleIds)->not->toContain($customerB->id, $spB->id);
});

test('scoped by sp filters retail tariff plans', function () {
    $spA = User::factory()->sp()->create();
    $spB = User::factory()->sp()->create();

    $planA = TariffPlan::withoutGlobalScopes()->create([
        'name' => 'A Retail',
        'type' => 'standard',
        'status' => 'active',
        'created_by' => $spA->id,
        'sp_id' => $spA->id,
    ]);

    $planB = TariffPlan::withoutGlobalScopes()->create([
        'name' => 'B Retail',
        'type' => 'standard',
        'status' => 'active',
        'created_by' => $spB->id,
        'sp_id' => $spB->id,
    ]);

    $platform = TariffPlan::withoutGlobalScopes()->create([
        'name' => 'Platform',
        'type' => 'standard',
        'status' => 'active',
        'created_by' => $spA->id,
        'sp_id' => null,
    ]);

    Auth::guard('sp')->login($spA);

    $visibleIds = TariffPlan::query()->pluck('id')->all();

    expect($visibleIds)->toContain($planA->id)
        ->and($visibleIds)->not->toContain($planB->id, $platform->id);
});

test('superman is not constrained by scoped by sp', function () {
    $sp = User::factory()->sp()->create();
    $customer = User::factory()->customerOf($sp)->create();
    $admin = User::factory()->superman()->create();

    Auth::guard('superman')->login($admin);

    expect(User::query()->pluck('id')->all())
        ->toContain($sp->id, $customer->id, $admin->id);
});

test('auth user resolution does not recurse through scoped by sp', function () {
    $admin = User::factory()->superman()->create();

    Auth::guard('superman')->login($admin);

    // Previously this infinite-looped: ScopedBySp → Auth::user() → User query → ScopedBySp.
    $resolved = Auth::guard('superman')->user();

    expect($resolved)->not->toBeNull()
        ->and($resolved->is($admin))->toBeTrue()
        ->and(User::query()->count())->toBeGreaterThan(0);
});

test('sp auth user resolution does not recurse through scoped by sp', function () {
    $sp = User::factory()->sp()->create();

    Auth::guard('sp')->login($sp);

    $resolved = Auth::guard('sp')->user();

    expect($resolved)->not->toBeNull()
        ->and($resolved->is($sp))->toBeTrue()
        ->and(User::query()->whereKey($sp->id)->exists())->toBeTrue();
});
