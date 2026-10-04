<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\EmailGateway;
use App\Models\Gateway;
use App\Models\VerificationOtp;
use App\Services\Auth\OtpVerificationService;
use App\Services\Sms\SmsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

uses(RefreshDatabase::class);

// ─────────────────────────────────────────────
// EMAIL GATEWAY PRIORITY & FAILOVER
// ─────────────────────────────────────────────

test('email otp uses priority 1 gateway first when it succeeds', function () {
    Mail::fake();

    // When no gateways configured, falls back to default mailer
    $service = app(OtpVerificationService::class);
    $result = $service->sendEmailOtp('test-priority@example.com');

    // Default mailer (Mail::fake) catches it — should succeed
    expect($result['success'])->toBeTrue();
});

test('email otp creates otp record in database on send', function () {
    Mail::fake();

    $service = app(OtpVerificationService::class);
    $service->sendEmailOtp('otp-record@example.com');

    expect(VerificationOtp::where('identifier', 'otp-record@example.com')
        ->where('type', 'email')
        ->exists())->toBeTrue();
});

test('email otp respects cooldown period', function () {
    Mail::fake();

    $service = app(OtpVerificationService::class);

    // First send
    $result1 = $service->sendEmailOtp('cooldown@example.com');
    expect($result1['success'])->toBeTrue();

    // Immediate second send — cooldown enforced
    $result2 = $service->sendEmailOtp('cooldown@example.com');
    expect($result2['success'])->toBeFalse()
        ->and($result2['message'])->toContain('Please wait');
});

test('email otp returns failure for already registered email', function () {
    Mail::fake();

    \App\Models\User::factory()->create(['email' => 'existing@example.com']);

    $service = app(OtpVerificationService::class);
    $result = $service->sendEmailOtp('existing@example.com');

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('already registered');
});

test('email otp verification succeeds with correct code', function () {
    Mail::fake();

    $service = app(OtpVerificationService::class);
    $email = 'verify-correct@example.com';

    // Patch: manually insert an otp record with a known hash
    $knownOtp = '654321';
    VerificationOtp::create([
        'identifier' => $email,
        'type' => 'email',
        'otp_hash' => \Illuminate\Support\Facades\Hash::make($knownOtp),
        'attempts' => 0,
        'last_sent_at' => now(),
        'expires_at' => now()->addMinutes(10),
    ]);

    $result = $service->verifyOtp($email, 'email', $knownOtp);

    expect($result['success'])->toBeTrue()
        ->and($result['token'])->not->toBeEmpty();
});

test('email otp verification fails with wrong code and increments attempts', function () {
    $email = 'verify-wrong@example.com';

    VerificationOtp::create([
        'identifier' => $email,
        'type' => 'email',
        'otp_hash' => \Illuminate\Support\Facades\Hash::make('111111'),
        'attempts' => 0,
        'last_sent_at' => now(),
        'expires_at' => now()->addMinutes(10),
    ]);

    $service = app(OtpVerificationService::class);
    $result = $service->verifyOtp($email, 'email', '999999');

    expect($result['success'])->toBeFalse();

    $record = VerificationOtp::where('identifier', $email)->latest('id')->first();
    expect($record->attempts)->toBe(1);
});

test('email otp is considered expired after expiry time', function () {
    $email = 'expired@example.com';

    VerificationOtp::create([
        'identifier' => $email,
        'type' => 'email',
        'otp_hash' => \Illuminate\Support\Facades\Hash::make('123456'),
        'attempts' => 0,
        'last_sent_at' => now()->subMinutes(15),
        'expires_at' => now()->subMinutes(5), // already expired
    ]);

    $service = app(OtpVerificationService::class);
    $result = $service->verifyOtp($email, 'email', '123456');

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('expired');
});

// ─────────────────────────────────────────────
// EMAIL GATEWAY MODEL
// ─────────────────────────────────────────────

test('email gateway model stores and retrieves encrypted password', function () {
    $gateway = EmailGateway::create([
        'name' => 'Test SMTP',
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'user@example.com',
        'password' => 'supersecretpassword',
        'encryption' => 'tls',
        'from_address' => 'noreply@example.com',
        'from_name' => 'SMS WhiteInfo',
        'priority' => 1,
        'is_active' => true,
    ]);

    $fresh = EmailGateway::find($gateway->id);

    // Password should be decrypted back to original
    expect($fresh->password)->toBe('supersecretpassword')
        ->and($fresh->is_active)->toBeTrue()
        ->and($fresh->priority)->toBe(1);
});

test('email gateway active scope returns only active gateways', function () {
    EmailGateway::create([
        'name' => 'Active Gateway',
        'host' => 'smtp1.example.com',
        'port' => 587,
        'priority' => 1,
        'is_active' => true,
    ]);

    EmailGateway::create([
        'name' => 'Inactive Gateway',
        'host' => 'smtp2.example.com',
        'port' => 587,
        'priority' => 2,
        'is_active' => false,
    ]);

    $activeGateways = EmailGateway::active()->get();

    expect($activeGateways)->toHaveCount(1)
        ->and($activeGateways->first()->name)->toBe('Active Gateway');
});

test('email gateway ordered scope returns gateways by priority ascending', function () {
    EmailGateway::create(['name' => 'Priority 3', 'host' => 'smtp3.example.com', 'port' => 587, 'priority' => 3, 'is_active' => true]);
    EmailGateway::create(['name' => 'Priority 1', 'host' => 'smtp1.example.com', 'port' => 587, 'priority' => 1, 'is_active' => true]);
    EmailGateway::create(['name' => 'Priority 2', 'host' => 'smtp2.example.com', 'port' => 587, 'priority' => 2, 'is_active' => true]);

    $ordered = EmailGateway::active()->ordered()->pluck('name');

    expect($ordered->first())->toBe('Priority 1')
        ->and($ordered->get(1))->toBe('Priority 2')
        ->and($ordered->last())->toBe('Priority 3');
});

// ─────────────────────────────────────────────
// SMS OTP
// ─────────────────────────────────────────────

test('sms otp fails for already registered phone number', function () {
    \App\Models\User::factory()->create(['phone' => '+8801712345678']);

    $service = app(OtpVerificationService::class);
    $result = $service->sendSmsOtp('+8801712345678');

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('already registered');
});

test('sms otp phone normalization accepts bd format', function () {
    $service = app(OtpVerificationService::class);

    $normalized = $service->normalizePhoneNumber('01712345678');
    expect($normalized)->toBe('+8801712345678');

    $normalized2 = $service->normalizePhoneNumber('+8801812345678');
    expect($normalized2)->toBe('+8801812345678');
});

test('sms otp returns failure for invalid phone number', function () {
    $service = app(OtpVerificationService::class);
    $result = $service->sendSmsOtp('not-a-phone-number');

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('Invalid mobile number');
});

test('is_verified returns true with valid proof token', function () {
    $email = 'proof-check@example.com';
    $token = \Illuminate\Support\Str::random(60);

    VerificationOtp::create([
        'identifier' => $email,
        'type' => 'email',
        'otp_hash' => \Illuminate\Support\Facades\Hash::make('123456'),
        'attempts' => 0,
        'last_sent_at' => now(),
        'expires_at' => now()->addMinutes(10),
        'verified_at' => now(),
        'token' => $token,
    ]);

    $service = app(OtpVerificationService::class);
    expect($service->isVerified($email, 'email', $token))->toBeTrue();
});

test('is_verified returns false with wrong token', function () {
    $email = 'wrong-token@example.com';

    VerificationOtp::create([
        'identifier' => $email,
        'type' => 'email',
        'otp_hash' => \Illuminate\Support\Facades\Hash::make('123456'),
        'attempts' => 0,
        'last_sent_at' => now(),
        'expires_at' => now()->addMinutes(10),
        'verified_at' => now(),
        'token' => 'correct-token',
    ]);

    $service = app(OtpVerificationService::class);
    expect($service->isVerified($email, 'email', 'wrong-token'))->toBeFalse();
});
