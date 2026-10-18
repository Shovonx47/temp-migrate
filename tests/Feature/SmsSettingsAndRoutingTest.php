<?php

use App\Models\Gateway;
use App\Models\Message;
use App\Models\RoutePlan;
use App\Models\RoutePlanGateway;
use App\Models\RouteRule;
use App\Models\Sender;
use App\Models\User;
use App\Services\Sms\GatewayHealthService;
use App\Services\Sms\Routing\SmsRouteResolver;
use App\Settings\SmsSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('loads sms settings with default values', function () {
    $settings = app(SmsSettings::class);

    expect($settings->sms_enabled)->toBeTrue()
        ->and($settings->default_sms_type)->toBe('plain_text')
        ->and($settings->default_encoding)->toBe('GSM-7')
        ->and($settings->max_sms_parts)->toBe(10)
        ->and($settings->validation_mode)->toBe('bangladesh_strict');
});

it('resolves priority gateways from route plan', function () {
    $plan = RoutePlan::firstOrCreate(
        ['code' => 'OTP_TEST'],
        [
            'name' => 'OTP Test Route',
            'traffic_type' => 'otp',
            'is_default' => true,
            'is_enabled' => true,
        ]
    );

    $gateway1 = Gateway::create([
        'name' => 'Primary Gateway',
        'driver' => \App\Enums\GatewayDriver::Log,
        'is_enabled' => true,
        'priority' => 1,
    ]);

    $gateway2 = Gateway::create([
        'name' => 'Secondary Gateway',
        'driver' => \App\Enums\GatewayDriver::Log,
        'is_enabled' => true,
        'priority' => 2,
    ]);

    RoutePlanGateway::create([
        'route_plan_id' => $plan->id,
        'gateway_id' => $gateway1->id,
        'priority' => 1,
        'is_active' => true,
    ]);

    RoutePlanGateway::create([
        'route_plan_id' => $plan->id,
        'gateway_id' => $gateway2->id,
        'priority' => 2,
        'is_active' => true,
    ]);

    $resolver = app(SmsRouteResolver::class);
    $gateways = $resolver->resolveGateways('+8801700000000', 'otp');

    expect($gateways)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($gateways->count())->toBe(2)
        ->and($gateways->first()->id)->toBe($gateway1->id)
        ->and($gateways->last()->id)->toBe($gateway2->id);
});

it('checks gateway health for log driver', function () {
    $gateway = Gateway::create([
        'name' => 'Test Log Gateway',
        'driver' => \App\Enums\GatewayDriver::Log,
        'is_enabled' => true,
        'priority' => 1,
        'timeout_seconds' => 10,
    ]);

    $healthService = app(GatewayHealthService::class);
    $health = $healthService->checkGateway($gateway);

    expect($health['status'])->toBe('healthy')
        ->and($health['balance'])->toBe('Unlimited');
});
