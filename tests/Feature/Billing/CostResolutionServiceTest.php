<?php

use App\Enums\DippingRateMode;
use App\Enums\MessageStatus;
use App\Models\Gateway;
use App\Models\GatewayDippingCharge;
use App\Models\GatewayOperatorCostPeriod;
use App\Models\Message;
use App\Models\Operator;
use App\Models\User;
use App\Services\Billing\CostResolutionService;
use App\Services\Billing\DippingChargeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedDefaultPricing();
});

it('resolves per-sms dipping charge for matched operator', function () {
    $gateway = Gateway::factory()->create();
    $operator = Operator::where('name', 'Grameenphone')->firstOrFail();

    GatewayDippingCharge::create([
        'gateway_id' => $gateway->id,
        'operator_id' => $operator->id,
        'masking_rate' => 0.055,
        'non_masking_rate' => 0.045,
        'rate_mode' => DippingRateMode::PerSms,
    ]);

    $resolver = DippingChargeResolver::forGateway($gateway);
    $result = $resolver->resolve('+8801711000001', false, 0.0, 2);

    expect($result['rate'])->toBe(0.045)
        ->and($result['cost'])->toBe(0.09);
});

it('resolves percentage dipping charge against operator cost total', function () {
    $gateway = Gateway::factory()->create();
    $operator = Operator::where('name', 'Grameenphone')->firstOrFail();

    GatewayDippingCharge::create([
        'gateway_id' => $gateway->id,
        'operator_id' => $operator->id,
        'masking_rate' => 10.0,
        'non_masking_rate' => 10.0,
        'rate_mode' => DippingRateMode::Percentage,
    ]);

    $resolver = DippingChargeResolver::forGateway($gateway);
    $result = $resolver->resolve('+8801711000001', false, 5.0, 1);

    expect($result['cost'])->toBe(0.5);
});

it('prefers effective-dated dipping charge over older rows', function () {
    $gateway = Gateway::factory()->create();
    $operator = Operator::where('name', 'Grameenphone')->firstOrFail();

    GatewayDippingCharge::create([
        'gateway_id' => $gateway->id,
        'operator_id' => $operator->id,
        'non_masking_rate' => 0.04,
        'rate_mode' => DippingRateMode::PerSms,
        'effective_from' => now()->subMonths(2)->toDateString(),
    ]);

    GatewayDippingCharge::create([
        'gateway_id' => $gateway->id,
        'operator_id' => $operator->id,
        'non_masking_rate' => 0.06,
        'rate_mode' => DippingRateMode::PerSms,
        'effective_from' => now()->subWeek()->toDateString(),
    ]);

    $resolver = DippingChargeResolver::forGateway($gateway);
    $result = $resolver->resolve('+8801711000001', false, 0.0, 1);

    expect($result['rate'])->toBe(0.06);
});

it('aggregates message-level operator and dipping costs by default', function () {
    $gateway = Gateway::factory()->create(['dipping_required' => false]);
    $user = User::factory()->create();

    Message::factory()->for($user)->count(3)->create([
        'gateway_id' => $gateway->id,
        'operator_cost' => 1.5,
        'primary_dipping_cost' => 0.5,
        'status' => MessageStatus::Sent,
        'created_at' => now(),
    ]);

    $cost = app(CostResolutionService::class)->resolveForGateway(
        $gateway,
        now()->startOfMonth(),
        now()->endOfMonth(),
    );

    expect($cost->operatorCost)->toBe(4.5)
        ->and($cost->primaryDippingCost)->toBe(1.5)
        ->and($cost->totalCost)->toBe(6.0);
});

it('keeps send failed and dlr failed as separate dlr metric counters', function () {
    $gateway = Gateway::factory()->create([
        'dlr_support' => true,
        'dipping_required' => false,
    ]);
    $user = User::factory()->create();

    Message::factory()->for($user)->create([
        'gateway_id' => $gateway->id,
        'status' => MessageStatus::Failed,
        'created_at' => now(),
    ]);

    Message::factory()->for($user)->create([
        'gateway_id' => $gateway->id,
        'status' => MessageStatus::DlrFailed,
        'created_at' => now(),
    ]);

    Message::factory()->for($user)->create([
        'gateway_id' => $gateway->id,
        'status' => MessageStatus::Delivered,
        'created_at' => now(),
    ]);

    $cost = app(CostResolutionService::class)->resolveForGateway(
        $gateway,
        now()->startOfMonth(),
        now()->endOfMonth(),
    );

    expect($cost->dlrMetrics?->sendFailed)->toBe(1)
        ->and($cost->dlrMetrics?->dlrFailed)->toBe(1)
        ->and($cost->dlrMetrics?->delivered)->toBe(1);
});

it('stores operator cost period variance on save', function () {
    $gateway = Gateway::factory()->create();

    $period = GatewayOperatorCostPeriod::create([
        'gateway_id' => $gateway->id,
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
        'manual_cost' => 20000,
        'api_cost' => 19500,
    ]);

    expect($period->cost_variance)->toBe(500.0);
});
