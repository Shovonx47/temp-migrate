<?php

use App\Enums\CostResolutionStatus;
use App\Enums\MessageStatus;
use App\Enums\ProfitStatus;
use App\Models\Gateway;
use App\Models\GatewayDippingCharge;
use App\Models\GatewayOperatorCostPeriod;
use App\Models\GatewayThirdPartyDippingPeriod;
use App\Models\Message;
use App\Models\User;
use App\Services\Billing\CostResolutionService;
use App\Services\Billing\GatewayProfitCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns incomplete profit when mandatory dipping gateway has no dipping configuration', function () {
    $gateway = Gateway::factory()->create([
        'dipping_required' => true,
        'dlr_support' => true,
    ]);

    $user = User::factory()->create();

    Message::factory()->for($user)->create([
        'gateway_id' => $gateway->id,
        'cost' => 10.0,
        'operator_cost' => 2.0,
        'primary_dipping_cost' => 0.0,
        'status' => MessageStatus::Sent,
        'created_at' => now(),
    ]);

    $result = app(GatewayProfitCalculationService::class)->calculateForGateway(
        $gateway,
        now()->startOfMonth(),
        now()->endOfMonth(),
    );

    expect($result->status)->toBe(ProfitStatus::Incomplete)
        ->and($result->profit)->toBeNull()
        ->and($result->incompleteReason)->toBe(CostResolutionService::MISSING_MANDATORY_DIPPING)
        ->and($result->revenue->total)->toBe(10.0);
});

it('computes profit when mandatory dipping gateway has dipping configuration', function () {
    $gateway = Gateway::factory()->create([
        'dipping_required' => true,
    ]);

    GatewayDippingCharge::create([
        'gateway_id' => $gateway->id,
        'operator_id' => null,
        'masking_rate' => 0.05,
        'non_masking_rate' => 0.04,
        'rate_mode' => 'per_sms',
    ]);

    $user = User::factory()->create();

    Message::factory()->for($user)->create([
        'gateway_id' => $gateway->id,
        'cost' => 20.0,
        'operator_cost' => 5.0,
        'primary_dipping_cost' => 3.0,
        'status' => MessageStatus::Sent,
        'created_at' => now(),
    ]);

    $result = app(GatewayProfitCalculationService::class)->calculateForGateway(
        $gateway,
        now()->startOfMonth(),
        now()->endOfMonth(),
    );

    expect($result->status)->toBe(ProfitStatus::Complete)
        ->and($result->profit)->toBe(12.0)
        ->and($result->cost->status)->toBe(CostResolutionStatus::Complete)
        ->and($result->cost->primaryDippingCost)->toBe(3.0);
});

it('uses period-level manual operator cost when present', function () {
    $gateway = Gateway::factory()->create([
        'dipping_required' => false,
    ]);

    $user = User::factory()->create();

    Message::factory()->for($user)->create([
        'gateway_id' => $gateway->id,
        'operator_cost' => 5.0,
        'primary_dipping_cost' => 1.0,
        'cost' => 15.0,
        'status' => MessageStatus::Sent,
        'created_at' => now(),
    ]);

    GatewayOperatorCostPeriod::create([
        'gateway_id' => $gateway->id,
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
        'manual_cost' => 20.0,
        'api_cost' => 18.0,
    ]);

    $cost = app(CostResolutionService::class)->resolveForGateway(
        $gateway,
        now()->startOfMonth(),
        now()->endOfMonth(),
    );

    expect($cost->operatorCost)->toBe(20.0);
});

it('never derives revenue from gateway cost tables', function () {
    $gateway = Gateway::factory()->create([
        'dipping_required' => false,
    ]);

    $user = User::factory()->create();

    Message::factory()->for($user)->count(2)->create([
        'gateway_id' => $gateway->id,
        'cost' => 7.5,
        'operator_cost' => 1.0,
        'primary_dipping_cost' => 0.5,
        'status' => MessageStatus::Sent,
        'created_at' => now(),
    ]);

    $result = app(GatewayProfitCalculationService::class)->calculateForGateway(
        $gateway,
        now()->startOfMonth(),
        now()->endOfMonth(),
    );

    expect($result->revenue->total)->toBe(15.0);
});

it('uses third-party dipping period manual cost when configured', function () {
    $gateway = Gateway::factory()->create([
        'has_third_party_dipping' => true,
        'dipping_required' => false,
    ]);

    $user = User::factory()->create();

    Message::factory()->for($user)->create([
        'gateway_id' => $gateway->id,
        'cost' => 10.0,
        'operator_cost' => 2.0,
        'primary_dipping_cost' => 1.0,
        'third_party_dipping_cost' => 0.0,
        'status' => MessageStatus::Sent,
        'created_at' => now(),
    ]);

    GatewayThirdPartyDippingPeriod::create([
        'gateway_id' => $gateway->id,
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
        'dipped_sms_volume' => 500,
        'manual_cost' => 27.5,
    ]);

    $cost = app(CostResolutionService::class)->resolveForGateway(
        $gateway,
        now()->startOfMonth(),
        now()->endOfMonth(),
    );

    expect($cost->thirdPartyDippingCost)->toBe(27.5)
        ->and($cost->totalCost)->toBe(30.5);
});

it('returns null dlr metrics when gateway does not support dlr', function () {
    $gateway = Gateway::factory()->create([
        'dlr_support' => false,
        'dipping_required' => false,
    ]);

    $cost = app(CostResolutionService::class)->resolveForGateway(
        $gateway,
        now()->startOfMonth(),
        now()->endOfMonth(),
    );

    expect($cost->dlrMetrics)->toBeNull();
});
