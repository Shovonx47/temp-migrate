<?php

use App\Enums\KycStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Filament\Customer\Pages\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Filament\Facades\Filament;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('customer'));
});

uses(RefreshDatabase::class);

const LOGIN_PASSWORD = 'Password123!';

test('pending customer with correct password gets account-under-review message instead of generic credential error', function () {
    User::factory()->create([
        'type' => UserType::Customer,
        'email' => 'pending@example.com',
        'password' => Hash::make(LOGIN_PASSWORD),
        'status' => UserStatus::Pending,
        'kyc_status' => KycStatus::UnderReview,
        'email_verified_at' => now(),
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'pending@example.com',
            'password' => LOGIN_PASSWORD,
        ])
        ->call('authenticate')
        ->assertHasErrors(['data.email'])
        ->assertSeeHtml('under review by the admin');

    $this->assertGuest('customer');
});

test('pending customer whose kyc was never submitted also gets the under-review message, not a raw credentials error', function () {
    User::factory()->create([
        'type' => UserType::Customer,
        'email' => 'neversubmitted@example.com',
        'password' => Hash::make(LOGIN_PASSWORD),
        'status' => UserStatus::Pending,
        'kyc_status' => KycStatus::Pending,
        'front_nid' => null,
        'email_verified_at' => now(),
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'neversubmitted@example.com',
            'password' => LOGIN_PASSWORD,
        ])
        ->call('authenticate')
        ->assertHasErrors(['data.email'])
        ->assertSeeHtml('under review by the admin');

    $this->assertGuest('customer');
});

test('approved customer whose kyc_status later reads under_review is NOT blocked — logs in normally', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'type' => UserType::Customer,
        'email' => 'kycreview@example.com',
        'password' => Hash::make(LOGIN_PASSWORD),
        'status' => UserStatus::Approved,
        'kyc_status' => KycStatus::UnderReview,
        'email_verified_at' => now(),
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'kycreview@example.com',
            'password' => LOGIN_PASSWORD,
        ])
        ->call('authenticate')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($user, 'customer');
});

test('banned customer gets correct banned message when password matches', function () {
    User::factory()->create([
        'type' => UserType::Customer,
        'email' => 'banned@example.com',
        'password' => Hash::make(LOGIN_PASSWORD),
        'status' => UserStatus::Banned,
        'kyc_status' => KycStatus::Verified,
        'email_verified_at' => now(),
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'banned@example.com',
            'password' => LOGIN_PASSWORD,
        ])
        ->call('authenticate')
        ->assertHasErrors(['data.email'])
        ->assertSeeHtml('banned');

    $this->assertGuest('customer');
});

test('rejected customer gets correct rejected message when password matches', function () {
    User::factory()->create([
        'type' => UserType::Customer,
        'email' => 'rejected@example.com',
        'password' => Hash::make(LOGIN_PASSWORD),
        'status' => UserStatus::Rejected,
        'kyc_status' => KycStatus::Rejected,
        'email_verified_at' => now(),
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'rejected@example.com',
            'password' => LOGIN_PASSWORD,
        ])
        ->call('authenticate')
        ->assertHasErrors(['data.email'])
        ->assertSeeHtml('rejected');

    $this->assertGuest('customer');
});

test('customer with ResubmitRequired KYC is NOT blocked by the pre-check — falls through to normal login so HandleCustomerKyc can route them', function () {
    // Mirrors the real transition made by RequestKycResubmissionAction:
    // status is set back to Pending alongside kyc_status = ResubmitRequired.
    /** @var User $user */
    $user = User::factory()->create([
        'type' => UserType::Customer,
        'email' => 'resubmit@example.com',
        'password' => Hash::make(LOGIN_PASSWORD),
        'status' => UserStatus::Pending,
        'kyc_status' => KycStatus::ResubmitRequired,
        'front_nid' => 'existing/front_nid.jpg',
        'email_verified_at' => now(),
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'resubmit@example.com',
            'password' => LOGIN_PASSWORD,
        ])
        ->call('authenticate')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($user, 'customer');
});

test('wrong password still uses the generic credentials message regardless of account status', function () {
    User::factory()->create([
        'type' => UserType::Customer,
        'email' => 'pending2@example.com',
        'password' => Hash::make(LOGIN_PASSWORD),
        'status' => UserStatus::Pending,
        'kyc_status' => KycStatus::UnderReview,
        'email_verified_at' => now(),
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'pending2@example.com',
            'password' => 'WrongPassword!',
        ])
        ->call('authenticate')
        ->assertHasErrors(['data.email'])
        ->assertDontSeeHtml('under review by the admin');

    $this->assertGuest('customer');
});

test('approved and verified customer logs in normally with no pre-check interference', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'type' => UserType::Customer,
        'email' => 'approved@example.com',
        'password' => Hash::make(LOGIN_PASSWORD),
        'status' => UserStatus::Approved,
        'kyc_status' => KycStatus::Verified,
        'email_verified_at' => now(),
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'approved@example.com',
            'password' => LOGIN_PASSWORD,
        ])
        ->call('authenticate')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($user, 'customer');
});

test('non-existent email falls through to generic credential error and never leaks status', function () {
    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'never-existed@example.com',
            'password' => LOGIN_PASSWORD,
        ])
        ->call('authenticate')
        ->assertHasErrors(['data.email'])
        ->assertDontSeeHtml('under review by the admin')
        ->assertDontSeeHtml('banned')
        ->assertDontSeeHtml('rejected');

    $this->assertGuest('customer');
});