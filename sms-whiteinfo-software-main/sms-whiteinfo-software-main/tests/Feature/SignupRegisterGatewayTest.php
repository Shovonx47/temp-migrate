<?php

declare(strict_types=1);

use App\Enums\UserType;
use App\Filament\Admin\Resources\Settings\SignupRegisterGateways\Pages\CreateSignupRegisterGateway;
use App\Filament\Admin\Resources\Settings\SignupRegisterGateways\Pages\ListSignupRegisterGateways;
use App\Filament\Admin\Resources\Settings\SignupRegisterGateways\SignupRegisterGatewayResource;
use App\Models\SignupRegisterGateway;
use App\Models\User;
use App\Models\VerificationOtp;
use App\Services\Auth\OtpVerificationService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('signup register gateway stores and retrieves encrypted bearer token', function () {
    $gateway = SignupRegisterGateway::factory()->create([
        'bearer_token' => 'super-secret-token',
    ]);

    $fresh = SignupRegisterGateway::query()->find($gateway->id);

    expect($fresh->bearer_token)->toBe('super-secret-token');
});

test('signup register gateway active scope excludes inactive records', function () {
    SignupRegisterGateway::factory()->create(['name' => 'Active API', 'priority' => 1]);
    SignupRegisterGateway::factory()->inactive()->create(['name' => 'Inactive API', 'priority' => 2]);

    $active = SignupRegisterGateway::query()->active()->get();

    expect($active)->toHaveCount(1)
        ->and($active->first()->name)->toBe('Active API');
});

test('signup register gateway ordered scope returns lowest priority first', function () {
    SignupRegisterGateway::factory()->create(['name' => 'Third', 'priority' => 3]);
    SignupRegisterGateway::factory()->create(['name' => 'First', 'priority' => 1]);
    SignupRegisterGateway::factory()->create(['name' => 'Second', 'priority' => 2]);

    $ordered = SignupRegisterGateway::query()->active()->ordered()->pluck('name');

    expect($ordered->all())->toBe(['First', 'Second', 'Third']);
});

test('phone otp posts to signup register gateway like the customer messages api', function () {
    Http::fake([
        'https://sms.whiteinfobd.com/api/v1/messages' => Http::response([
            'batch_id' => 47,
            'status' => 'processing',
            'approval' => null,
            'recipients' => 1,
        ], 202),
    ]);

    SignupRegisterGateway::factory()->create([
        'endpoint_url' => 'https://sms.whiteinfobd.com/api/v1/messages',
        'bearer_token' => 'test-signup-token',
        'sender_id' => 'FROLAX',
        'phone_format' => 'local_bd',
    ]);

    $result = app(OtpVerificationService::class)->sendSmsOtp('01712345678');

    expect($result['success'])->toBeTrue();

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://sms.whiteinfobd.com/api/v1/messages'
            && $request->hasHeader('Authorization', 'Bearer test-signup-token')
            && $request['sender_id'] === 'FROLAX'
            && $request['numbers'] === '01712345678'
            && str_contains((string) $request['body'], 'verification code');
    });

    expect(VerificationOtp::query()->where('identifier', '+8801712345678')->where('type', 'sms')->exists())->toBeTrue();
});

test('phone otp fails over to the next signup register gateway when the first http call fails', function () {
    Http::fake([
        'https://fail.example.com/api/v1/messages' => Http::response(['message' => 'Insufficient balance'], 402),
        'https://sms.whiteinfobd.com/api/v1/messages' => Http::response([
            'batch_id' => 48,
            'status' => 'processing',
            'recipients' => 1,
        ], 202),
    ]);

    SignupRegisterGateway::factory()->create([
        'name' => 'Failing',
        'endpoint_url' => 'https://fail.example.com/api/v1/messages',
        'priority' => 1,
    ]);

    SignupRegisterGateway::factory()->create([
        'name' => 'Working',
        'endpoint_url' => 'https://sms.whiteinfobd.com/api/v1/messages',
        'priority' => 2,
    ]);

    $result = app(OtpVerificationService::class)->sendSmsOtp('01812345678');

    expect($result['success'])->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://fail.example.com/api/v1/messages');
    Http::assertSent(fn ($request): bool => $request->url() === 'https://sms.whiteinfobd.com/api/v1/messages');
});

test('inactive signup register gateways are not used for phone otp', function () {
    Http::fake();

    SignupRegisterGateway::factory()->inactive()->create();

    $result = app(OtpVerificationService::class)->sendSmsOtp('01612345678');

    expect($result['success'])->toBeTrue();
    Http::assertNothingSent();
});

test('phone otp still uses the existing fallback when no signup register gateway is configured', function () {
    Http::fake();

    $result = app(OtpVerificationService::class)->sendSmsOtp('01512345678');

    expect($result['success'])->toBeTrue();
    Http::assertNothingSent();
});

test('admin can create a signup register gateway from settings', function () {
    Filament::setCurrentPanel(Filament::getPanel('superman'));

    $admin = User::factory()->create([
        'type' => UserType::Superman,
        'phone' => '+8801711111199',
    ]);
    actingAs($admin, 'superman');

    Livewire::test(CreateSignupRegisterGateway::class, ['resource' => SignupRegisterGatewayResource::class])
        ->fillForm([
            'name' => 'Production WhiteInfo',
            'priority' => 1,
            'endpoint_url' => 'https://sms.whiteinfobd.com/api/v1/messages',
            'http_method' => 'POST',
            'timeout_seconds' => 15,
            'bearer_token' => 'panel-created-token',
            'sender_id' => 'FROLAX',
            'phone_format' => 'local_bd',
            'body_template' => 'Your SMS WhiteInfo verification code is: {otp}. Valid for {minutes} minutes.',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $gateway = SignupRegisterGateway::query()->where('name', 'Production WhiteInfo')->first();

    expect($gateway)->not->toBeNull()
        ->and($gateway->sender_id)->toBe('FROLAX')
        ->and($gateway->bearer_token)->toBe('panel-created-token');
});

test('admin can list signup register gateways', function () {
    Filament::setCurrentPanel(Filament::getPanel('superman'));

    $admin = User::factory()->create([
        'type' => UserType::Superman,
        'phone' => '+8801711111188',
    ]);
    actingAs($admin, 'superman');

    $gateways = SignupRegisterGateway::factory()->count(2)->create();

    Livewire::test(ListSignupRegisterGateways::class, ['resource' => SignupRegisterGatewayResource::class])
        ->assertCanSeeTableRecords($gateways);
});
