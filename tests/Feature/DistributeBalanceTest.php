<?php

use App\Actions\Billing\DistributeBalance;
use App\Models\SpDistribution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sp can distribute balance to owned customer atomically', function () {
    $sp = User::factory()->sp()->create(['balance' => 1000]);
    $customer = User::factory()->customerOf($sp)->create(['balance' => 50]);

    $distribution = app(DistributeBalance::class)->handle(
        $sp,
        $customer,
        250,
        'Welcome credit',
        $sp,
    );

    expect($distribution)->toBeInstanceOf(SpDistribution::class)
        ->and($distribution->amount)->toEqual(250.0)
        ->and($distribution->note)->toBe('Welcome credit')
        ->and($sp->fresh()->balance)->toEqual(750.0)
        ->and($customer->fresh()->balance)->toEqual(300.0)
        ->and($distribution->transaction_id)->not->toBeNull();
});

test('sp cannot distribute to another sps customer', function () {
    $sp = User::factory()->sp()->create(['balance' => 1000]);
    $otherSp = User::factory()->sp()->create(['balance' => 1000]);
    $foreignCustomer = User::factory()->customerOf($otherSp)->create(['balance' => 0]);

    app(DistributeBalance::class)->handle($sp, $foreignCustomer, 100);
})->throws(InvalidArgumentException::class, 'Target must be a customer owned by this S P.');

test('sp cannot distribute more than available balance', function () {
    $sp = User::factory()->sp()->create(['balance' => 100]);
    $customer = User::factory()->customerOf($sp)->create(['balance' => 0]);

    app(DistributeBalance::class)->handle($sp, $customer, 150);
})->throws(RuntimeException::class, 'Insufficient balance.');

test('full distribution to zero is allowed', function () {
    $sp = User::factory()->sp()->create(['balance' => 500]);
    $customer = User::factory()->customerOf($sp)->create(['balance' => 0]);

    app(DistributeBalance::class)->handle($sp, $customer, 500, 'Full drain');

    expect($sp->fresh()->balance)->toEqual(0.0)
        ->and($customer->fresh()->balance)->toEqual(500.0);
});

test('sequential distributions cannot over-debit below zero', function () {
    $sp = User::factory()->sp()->create(['balance' => 100]);
    $customerA = User::factory()->customerOf($sp)->create(['balance' => 0]);
    $customerB = User::factory()->customerOf($sp)->create(['balance' => 0]);

    $action = app(DistributeBalance::class);
    $action->handle($sp->fresh(), $customerA, 80);

    expect(fn () => $action->handle($sp->fresh(), $customerB, 80))
        ->toThrow(RuntimeException::class, 'Insufficient balance.');

    expect($sp->fresh()->balance)->toEqual(20.0)
        ->and($customerA->fresh()->balance)->toEqual(80.0)
        ->and($customerB->fresh()->balance)->toEqual(0.0);
});
