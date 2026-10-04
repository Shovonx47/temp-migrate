<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Filament\Customer\Pages\Register;
use App\Models\User;
use App\Models\VerificationOtp;
use App\Services\Auth\OtpVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('registration succeeds after user is soft-deleted with same email and phone', function () {
    Storage::fake('private');
    Storage::fake('public');

    // Create and soft-delete a user with the target email/phone/NID.
    $oldUser = User::factory()->create([
        'email' => 'reregister@example.com',
        'phone' => '+8801712345678',
        'nid_number' => '1234567890123',
    ]);
    $oldUser->delete(); // Soft-delete — record stays in DB.

    // Ensure the soft-deleted record still exists in the DB.
    expect(User::withTrashed()->where('email', 'reregister@example.com')->exists())->toBeTrue();
    expect(User::where('email', 'reregister@example.com')->exists())->toBeFalse();

    // Prepare verified OTP tokens.
    VerificationOtp::create([
        'identifier' => 'reregister@example.com',
        'type' => 'email',
        'otp_hash' => Hash::make('123456'),
        'token' => 'email-re-tok',
        'verified_at' => now(),
    ]);
    VerificationOtp::create([
        'identifier' => '+8801712345678',
        'type' => 'sms',
        'otp_hash' => Hash::make('123456'),
        'token' => 'sms-re-tok',
        'verified_at' => now(),
    ]);

    $file1 = UploadedFile::fake()->image('front.jpg');
    $file2 = UploadedFile::fake()->image('back.jpg');
    $licenseDoc = UploadedFile::fake()->create('license.pdf', 100);

    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Re-Registered User',
            'email' => 'reregister@example.com',
            'phone' => '+8801712345678',
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
            'nid_number' => '1234567890123',
            'front_nid' => [$file1],
            'back_nid' => [$file2],
            'business_name' => 'New Business',
            'trade_license_number' => 'TL-111',
            'business_address' => '123 Test Street',
            'trade_license_file' => [$licenseDoc],
            'terms' => true,
        ])
        ->set('email_verification_token', 'email-re-tok')
        ->set('phone_verification_token', 'sms-re-tok')
        ->set('data.live_photo', 'data:image/jpeg;base64,/9j/4AAQSkZJRg==')
        ->call('register')
        ->assertHasNoFormErrors();

    // The soft-deleted record should have been force-deleted.
    expect(User::withTrashed()->where('id', $oldUser->id)->exists())->toBeFalse();

    // The new user should exist.
    $newUser = User::where('email', 'reregister@example.com')->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->name)->toBe('Re-Registered User');
    expect($newUser->status)->toBe(UserStatus::Pending);
});

test('email OTP sending is not blocked by a soft-deleted user', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'softdeleted@example.com',
    ]);
    $user->delete();

    $otpService = app(OtpVerificationService::class);
    $result = $otpService->sendEmailOtp('softdeleted@example.com');

    expect($result['success'])->toBeTrue();
});

test('sms OTP sending is not blocked by a soft-deleted user', function () {
    $user = User::factory()->create([
        'phone' => '+8801799999999',
    ]);
    $user->delete();

    $otpService = app(OtpVerificationService::class);
    $result = $otpService->sendSmsOtp('01799999999');

    expect($result['success'])->toBeTrue();
});

test('email OTP sending is still blocked by an active user', function () {
    Mail::fake();

    User::factory()->create([
        'email' => 'active@example.com',
    ]);

    $otpService = app(OtpVerificationService::class);
    $result = $otpService->sendEmailOtp('active@example.com');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('This email address is already registered.');
});

test('sms OTP sending is still blocked by an active user', function () {
    User::factory()->create([
        'phone' => '+8801788888888',
    ]);

    $otpService = app(OtpVerificationService::class);
    $result = $otpService->sendSmsOtp('01788888888');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('This mobile number is already registered.');
});
