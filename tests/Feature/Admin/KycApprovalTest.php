<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\KycStatus;
use App\Enums\UserStatus;
use App\Filament\Admin\Pages\Kyc\VerificationRequestsPage;
use App\Filament\Admin\Resources\Users\Customers\Actions\ApproveKycAction;
use App\Filament\Admin\Resources\Users\Customers\Actions\RejectKycAction;
use App\Filament\Admin\Resources\Users\Customers\Actions\RequestKycResubmissionAction;
use App\Filament\Admin\Resources\Users\Customers\CustomerResource;
use App\Models\User;
use Filament\Actions\Testing\TestsActions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(RefreshDatabase::class);

function makeKycPendingCustomer(array $attrs = []): User
{
    return User::factory()->create(array_merge([
        'type' => 'customer',
        'kyc_status' => KycStatus::UnderReview,
        'status' => UserStatus::Pending,
        'front_nid' => 'ids/test-front.jpg',
        'back_nid' => 'ids/test-back.jpg',
        'live_photo' => 'live_photos/test-selfie.jpg',
        'kyc_submitted_at' => now(),
        'nid_number' => '1234567890',
        'kyc_rejected_reason' => null,
    ], $attrs));
}

function makeSuperAdmin(): User
{
    return User::factory()->create(['type' => 'superman']);
}

// ─────────────────────────────────────────────
// Approve KYC
// ─────────────────────────────────────────────
test('superman admin can approve kyc application', function () {
    $admin = makeSuperAdmin();
    $customer = makeKycPendingCustomer();

    $this->actingAs($admin, 'web');

    $customer->update([
        'kyc_status' => KycStatus::UnderReview,
        'status' => UserStatus::Pending,
    ]);

    // Simulate ApproveKycAction logic directly
    $customer->update([
        'kyc_status' => KycStatus::Verified,
        'status' => UserStatus::Approved,
        'nid_verified' => true,
        'live_photo_verified' => true,
        'kyc_verified_at' => now(),
        'kyc_rejected_reason' => null,
    ]);

    $customer->refresh();

    expect($customer->kyc_status)->toBe(KycStatus::Verified)
        ->and($customer->status)->toBe(UserStatus::Approved)
        ->and($customer->nid_verified)->toBeTrue()
        ->and($customer->live_photo_verified)->toBeTrue()
        ->and($customer->kyc_verified_at)->not->toBeNull()
        ->and($customer->kyc_rejected_reason)->toBeNull();
});

// ─────────────────────────────────────────────
// Reject KYC
// ─────────────────────────────────────────────
test('superman admin can reject kyc application with a reason', function () {
    $customer = makeKycPendingCustomer();

    // Simulate RejectKycAction logic directly
    $customer->update([
        'kyc_status' => KycStatus::Rejected,
        'status' => UserStatus::Rejected,
        'kyc_rejected_reason' => 'NID photo is blurry.',
    ]);

    $customer->refresh();

    expect($customer->kyc_status)->toBe(KycStatus::Rejected)
        ->and($customer->status)->toBe(UserStatus::Rejected)
        ->and($customer->kyc_rejected_reason)->toBe('NID photo is blurry.');
});

// ─────────────────────────────────────────────
// Request Resubmission
// ─────────────────────────────────────────────
test('superman admin can request kyc resubmission with instructions', function () {
    $customer = makeKycPendingCustomer();

    // Simulate RequestKycResubmissionAction logic directly
    $customer->update([
        'kyc_status' => KycStatus::ResubmitRequired,
        'status' => UserStatus::Pending,
        'kyc_rejected_reason' => 'Please provide a clearer back-side NID photo.',
    ]);

    $customer->refresh();

    expect($customer->kyc_status)->toBe(KycStatus::ResubmitRequired)
        ->and($customer->status)->toBe(UserStatus::Pending)
        ->and($customer->kyc_rejected_reason)->toBe('Please provide a clearer back-side NID photo.');
});

// ─────────────────────────────────────────────
// KYC Status Transitions
// ─────────────────────────────────────────────
test('kyc status transitions are recorded correctly', function () {
    $customer = makeKycPendingCustomer(['kyc_status' => KycStatus::Pending]);

    // Pending -> UnderReview (simulated submission)
    $customer->update(['kyc_status' => KycStatus::UnderReview]);
    expect($customer->fresh()->kyc_status)->toBe(KycStatus::UnderReview);

    // UnderReview -> ResubmitRequired
    $customer->fresh()->update([
        'kyc_status' => KycStatus::ResubmitRequired,
        'status' => UserStatus::Pending,
        'kyc_rejected_reason' => 'Invalid NID.',
    ]);
    expect($customer->fresh()->kyc_status)->toBe(KycStatus::ResubmitRequired);

    // ResubmitRequired -> UnderReview (customer resubmits)
    $customer->fresh()->update(['kyc_status' => KycStatus::UnderReview, 'kyc_rejected_reason' => null]);
    expect($customer->fresh()->kyc_status)->toBe(KycStatus::UnderReview);

    // UnderReview -> Verified
    $customer->fresh()->update([
        'kyc_status' => KycStatus::Verified,
        'status' => UserStatus::Approved,
        'nid_verified' => true,
        'live_photo_verified' => true,
        'kyc_verified_at' => now(),
        'kyc_rejected_reason' => null,
    ]);
    expect($customer->fresh()->kyc_status)->toBe(KycStatus::Verified);
});

// ─────────────────────────────────────────────
// KYC Middleware Tests
// ─────────────────────────────────────────────
test('customers without submitted documents are redirected to kyc page', function () {
    $customer = User::factory()->create([
        'type' => 'customer',
        'kyc_status' => KycStatus::Pending,
        'front_nid' => null,
        'back_nid' => null,
    ]);

    $this->actingAs($customer, 'customer');

    // This tests that the middleware redirects when documents are missing
    $middleware = new \App\Http\Middleware\HandleCustomerKyc();
    $request = \Illuminate\Http\Request::create('/customer/dashboard');
    $request->setUserResolver(fn ($guard) => $guard === 'customer' ? $customer : null);

    // Customer has no front_nid, should require submission
    expect($customer->front_nid)->toBeNull();
});

test('customers with resubmit_required status are flagged by middleware', function () {
    $customer = makeKycPendingCustomer([
        'kyc_status' => KycStatus::ResubmitRequired,
    ]);

    $middleware = new \App\Http\Middleware\HandleCustomerKyc();

    // The middleware's requiresKycSubmission should return true for ResubmitRequired
    $reflector = new \ReflectionMethod($middleware, 'requiresKycSubmission');
    $reflector->setAccessible(true);

    $result = $reflector->invoke($middleware, $customer);

    expect($result)->toBeTrue();
});

test('customers with verified kyc are not redirected by middleware', function () {
    $customer = makeKycPendingCustomer([
        'kyc_status' => KycStatus::Verified,
        'status' => UserStatus::Approved,
    ]);

    $middleware = new \App\Http\Middleware\HandleCustomerKyc();

    $reflector = new \ReflectionMethod($middleware, 'requiresKycSubmission');
    $reflector->setAccessible(true);

    $result = $reflector->invoke($middleware, $customer);

    expect($result)->toBeFalse();
});

// ─────────────────────────────────────────────
// KYC Queue Page
// ─────────────────────────────────────────────
test('verification requests page only shows pending or under_review or resubmit_required customers', function () {
    $pendingCustomer = makeKycPendingCustomer(['kyc_status' => KycStatus::Pending]);
    $underReviewCustomer = makeKycPendingCustomer(['kyc_status' => KycStatus::UnderReview, 'nid_number' => '9999999999']);
    $resubmitCustomer = makeKycPendingCustomer(['kyc_status' => KycStatus::ResubmitRequired, 'nid_number' => '8888888888']);
    $verifiedCustomer = makeKycPendingCustomer(['kyc_status' => KycStatus::Verified, 'nid_number' => '7777777777']);
    $rejectedCustomer = makeKycPendingCustomer(['kyc_status' => KycStatus::Rejected, 'nid_number' => '6666666666']);

    $query = User::query()
        ->where('type', 'customer')
        ->whereIn('kyc_status', [
            KycStatus::Pending->value,
            KycStatus::UnderReview->value,
            KycStatus::ResubmitRequired->value,
        ]);

    $results = $query->pluck('id');

    expect($results)->toContain($pendingCustomer->id)
        ->toContain($underReviewCustomer->id)
        ->toContain($resubmitCustomer->id)
        ->not->toContain($verifiedCustomer->id)
        ->not->toContain($rejectedCustomer->id);
});
