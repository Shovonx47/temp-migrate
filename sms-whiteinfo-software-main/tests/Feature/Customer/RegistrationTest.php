<?php

use App\Enums\KycStatus;
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

test('registration page renders successfully', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('Personal Information')
        ->assertSee('NID / KYC Verification')
        ->assertSee('Business Information (Optional)');
});

test('email otp can be sent and verified', function () {
    Mail::fake();
    $otpService = app(OtpVerificationService::class);

    $sendResult = $otpService->sendEmailOtp('user@example.com');
    expect($sendResult['success'])->toBeTrue();

    $otpRecord = VerificationOtp::where('identifier', 'user@example.com')
        ->where('type', 'email')
        ->first();

    expect($otpRecord)->not->toBeNull();
    expect($otpRecord->otp_hash)->not->toBeNull();

    // Verification with wrong OTP fails
    $failVerify = $otpService->verifyOtp('user@example.com', 'email', '000000');
    expect($failVerify['success'])->toBeFalse();

    // Verification with correct OTP succeeds
    $otpRecord->update(['otp_hash' => Hash::make('123456')]);
    $successVerify = $otpService->verifyOtp('user@example.com', 'email', '123456');
    expect($successVerify['success'])->toBeTrue();
    expect($successVerify['token'])->not->toBeNull();

    // isVerified returns true with valid token
    expect($otpService->isVerified('user@example.com', 'email', $successVerify['token']))->toBeTrue();
});

test('sms otp can be sent and verified', function () {
    $otpService = app(OtpVerificationService::class);

    $sendResult = $otpService->sendSmsOtp('01712345678');
    expect($sendResult['success'])->toBeTrue();

    $normalizedPhone = '+8801712345678';
    $otpRecord = VerificationOtp::where('identifier', $normalizedPhone)
        ->where('type', 'sms')
        ->first();

    expect($otpRecord)->not->toBeNull();

    // Verify OTP
    $otpRecord->update(['otp_hash' => Hash::make('654321')]);
    $verifyResult = $otpService->verifyOtp('01712345678', 'sms', '654321');
    expect($verifyResult['success'])->toBeTrue();
    expect($otpService->isVerified($normalizedPhone, 'sms', $verifyResult['token']))->toBeTrue();
});

test('registration blocks account creation if email is not verified', function () {
    Storage::fake('private');
    Storage::fake('public');

    $file1 = UploadedFile::fake()->image('front.jpg');
    $file2 = UploadedFile::fake()->image('back.jpg');

    // Only verify SMS, leave Email unverified
    VerificationOtp::create([
        'identifier' => '+8801711111111',
        'type' => 'sms',
        'otp_hash' => Hash::make('123456'),
        'token' => 'valid-sms-token',
        'verified_at' => now(),
    ]);

    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'John Doe',
            'email' => 'unverified@example.com',
            'phone' => '+8801711111111',
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
            'nid_number' => '1234567890',
            'front_nid' => [$file1],
            'back_nid' => [$file2],
            'terms' => true,
        ])
        ->set('phone_verification_token', 'valid-sms-token')
        ->set('email_verification_token', null)
        ->call('register')
        ->assertHasErrors(['data.email']);
});

test('registration blocks account creation if mobile is not verified', function () {
    Storage::fake('private');
    Storage::fake('public');

    $file1 = UploadedFile::fake()->image('front.jpg');
    $file2 = UploadedFile::fake()->image('back.jpg');

    // Only verify Email, leave Phone unverified
    VerificationOtp::create([
        'identifier' => 'verified@example.com',
        'type' => 'email',
        'otp_hash' => Hash::make('123456'),
        'token' => 'valid-email-token',
        'verified_at' => now(),
    ]);

    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'John Doe',
            'email' => 'verified@example.com',
            'phone' => '+8801711111111',
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
            'nid_number' => '1234567890',
            'front_nid' => [$file1],
            'back_nid' => [$file2],
            'terms' => true,
        ])
        ->set('email_verification_token', 'valid-email-token')
        ->set('phone_verification_token', null)
        ->call('register')
        ->assertHasErrors(['data.phone']);
});

test('registration blocks duplicate NID number', function () {
    Storage::fake('private');
    Storage::fake('public');

    $file1 = UploadedFile::fake()->image('front.jpg');
    $file2 = UploadedFile::fake()->image('back.jpg');

    User::factory()->create([
        'nid_number' => '9999888877',
    ]);

    VerificationOtp::create([
        'identifier' => 'user2@example.com',
        'type' => 'email',
        'otp_hash' => Hash::make('123456'),
        'token' => 'email-tok',
        'verified_at' => now(),
    ]);
    VerificationOtp::create([
        'identifier' => '+8801722222222',
        'type' => 'sms',
        'otp_hash' => Hash::make('123456'),
        'token' => 'sms-tok',
        'verified_at' => now(),
    ]);

    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'New User',
            'email' => 'user2@example.com',
            'phone' => '+8801722222222',
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
            'nid_number' => '9999888877', // Duplicate NID
            'front_nid' => [$file1],
            'back_nid' => [$file2],
            'terms' => true,
        ])
        ->set('email_verification_token', 'email-tok')
        ->set('phone_verification_token', 'sms-tok')
        ->call('register')
        ->assertHasErrors(['data.nid_number']);
});

test('registration succeeds with optional trade license omitted', function () {
    Storage::fake('private');
    Storage::fake('public');

    $file1 = UploadedFile::fake()->image('front.jpg');
    $file2 = UploadedFile::fake()->image('back.jpg');

    VerificationOtp::create([
        'identifier' => 'newcustomer@example.com',
        'type' => 'email',
        'otp_hash' => Hash::make('123456'),
        'token' => 'email-token-1',
        'verified_at' => now(),
    ]);
    VerificationOtp::create([
        'identifier' => '+8801733333333',
        'type' => 'sms',
        'otp_hash' => Hash::make('123456'),
        'token' => 'sms-token-1',
        'verified_at' => now(),
    ]);

    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Complete Customer',
            'email' => 'newcustomer@example.com',
            'phone' => '+8801733333333',
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
            'nid_number' => '5555444433',
            'front_nid' => [$file1],
            'back_nid' => [$file2],
            'business_name' => null,
            'trade_license_number' => null,
            'trade_license_file' => [],
            'terms' => true,
        ])
        ->set('email_verification_token', 'email-token-1')
        ->set('phone_verification_token', 'sms-token-1')
        ->call('register')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'newcustomer@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->status)->toBe(UserStatus::Pending);
    expect($user->kyc_status)->toBe(KycStatus::UnderReview);
    expect($user->nid_number)->toBe('5555444433');
    expect($user->trade_license_submitted)->toBeFalse();
    expect($user->trade_license_number)->toBeNull();
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->phone_verified_at)->not->toBeNull();
});

test('registration succeeds with trade license provided', function () {
    Storage::fake('private');
    Storage::fake('public');

    $file1 = UploadedFile::fake()->image('front.jpg');
    $file2 = UploadedFile::fake()->image('back.jpg');
    $licenseDoc = UploadedFile::fake()->create('license.pdf', 100);

    VerificationOtp::create([
        'identifier' => 'bizcustomer@example.com',
        'type' => 'email',
        'otp_hash' => Hash::make('123456'),
        'token' => 'email-token-2',
        'verified_at' => now(),
    ]);
    VerificationOtp::create([
        'identifier' => '+8801744444444',
        'type' => 'sms',
        'otp_hash' => Hash::make('123456'),
        'token' => 'sms-token-2',
        'verified_at' => now(),
    ]);

    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Biz Customer',
            'email' => 'bizcustomer@example.com',
            'phone' => '+8801744444444',
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
            'nid_number' => '7777666655',
            'front_nid' => [$file1],
            'back_nid' => [$file2],
            'business_name' => 'Tech Corp',
            'trade_license_number' => 'TR-998877',
            'trade_license_file' => [$licenseDoc],
            'terms' => true,
        ])
        ->set('email_verification_token', 'email-token-2')
        ->set('phone_verification_token', 'sms-token-2')
        ->call('register')
        ->assertHasNoErrors();

    $user = User::where('email', 'bizcustomer@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->trade_license_submitted)->toBeTrue();
    expect($user->trade_license_number)->toBe('TR-998877');
    expect($user->business_name)->toBe('Tech Corp');
});
