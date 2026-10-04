<?php

use App\Enums\MessageStatus;
use App\Enums\ProfitStatus;
use App\Models\Gateway;
use App\Models\Message;
use App\Models\User;
use App\Services\Billing\GatewayProfitCalculationService;
use App\Services\Billing\RevenueResolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves revenue for period across all gateways when no gateway is specified', function () {
    $user = User::factory()->create();
    $gatewayA = Gateway::factory()->create();
    $gatewayB = Gateway::factory()->create();

    Message::factory()->for($user)->create([
        'gateway_id' => $gatewayA->id,
        'cost' => 12.50,
        'parts' => 2,
        'status' => MessageStatus::Sent,
        'created_at' => now(),
    ]);

    Message::factory()->for($user)->create([
        'gateway_id' => $gatewayB->id,
        'cost' => 7.50,
        'parts' => 1,
        'status' => MessageStatus::Delivered,
        'created_at' => now(),
    ]);

    Message::factory()->for($user)->create([
        'gateway_id' => $gatewayA->id,
        'cost' => 5.00,
        'parts' => 1,
        'status' => MessageStatus::Failed,
        'created_at' => now(),
    ]);

    $service = app(RevenueResolutionService::class);
    $result = $service->resolveForPeriod(now()->startOfMonth(), now()->endOfMonth());

    expect($result->total)->toBe(20.0)
        ->and($result->messageCount)->toBe(2)
        ->and($result->parts)->toBe(3);
});

it('resolves revenue scoped to a specific gateway', function () {
    $user = User::factory()->create();
    $gatewayA = Gateway::factory()->create();
    $gatewayB = Gateway::factory()->create();

    Message::factory()->for($user)->create([
        'gateway_id' => $gatewayA->id,
        'cost' => 15.0,
        'parts' => 1,
        'status' => MessageStatus::Sent,
        'created_at' => now(),
    ]);

    Message::factory()->for($user)->create([
        'gateway_id' => $gatewayB->id,
        'cost' => 25.0,
        'parts' => 2,
        'status' => MessageStatus::Sent,
        'created_at' => now(),
    ]);

    $service = app(RevenueResolutionService::class);
    $result = $service->resolveForPeriod(now()->startOfMonth(), now()->endOfMonth(), $gatewayA);

    expect($result->total)->toBe(15.0)
        ->and($result->messageCount)->toBe(1);
});

it('resolves revenue for a specific customer', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $gateway = Gateway::factory()->create();

    Message::factory()->for($userA)->create([
        'gateway_id' => $gateway->id,
        'cost' => 10.0,
        'parts' => 1,
        'status' => MessageStatus::Sent,
        'created_at' => now(),
    ]);

    Message::factory()->for($userB)->create([
        'gateway_id' => $gateway->id,
        'cost' => 30.0,
        'parts' => 3,
        'status' => MessageStatus::Sent,
        'created_at' => now(),
    ]);

    $service = app(RevenueResolutionService::class);
    $result = $service->resolveForCustomer($userA, now()->startOfMonth(), now()->endOfMonth());

    expect($result->total)->toBe(10.0)
        ->and($result->messageCount)->toBe(1);
});

it('calculates profit and profit margin correctly', function () {
    $gateway = Gateway::factory()->create(['dipping_required' => false]);
    $user = User::factory()->create();

    Message::factory()->for($user)->count(2)->create([
        'gateway_id' => $gateway->id,
        'cost' => 50.0,
        'operator_cost' => 20.0,
        'primary_dipping_cost' => 5.0,
        'status' => MessageStatus::Sent,
        'created_at' => now(),
    ]);

    $result = app(GatewayProfitCalculationService::class)->calculateForGateway(
        $gateway,
        now()->startOfMonth(),
        now()->endOfMonth(),
    );

    expect($result->status)->toBe(ProfitStatus::Complete)
        ->and($result->revenue->total)->toBe(100.0)
        ->and($result->cost->operatorCost)->toBe(40.0)
        ->and($result->cost->primaryDippingCost)->toBe(10.0)
        ->and($result->cost->totalCost)->toBe(50.0)
        ->and($result->profit)->toBe(50.0)
        ->and($result->margin)->toBe(50.0);
});
