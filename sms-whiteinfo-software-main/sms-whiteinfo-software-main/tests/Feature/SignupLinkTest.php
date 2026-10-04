<?php

use App\Enums\TariffPlanAssignmentStatus;
use App\Enums\TariffPlanStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Filament\Customer\Pages\Register;
use App\Models\SignupSlugAlias;
use App\Models\TariffPlan;
use App\Models\TariffPlanAssignment;
use App\Models\User;
use App\Services\Auth\OtpVerificationService;
use App\Services\TariffSlugService;
use Filament\Auth\Events\Registered;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class); // remove if tests/Pest.php already applies it to Feature tests

function signupPlan(array $attributes = []): TariffPlan
{
    return TariffPlan::create(array_merge([
        'name' => 'Basic',
        'status' => TariffPlanStatus::Active,
    ], $attributes));
}

function signupReseller(UserStatus $status = UserStatus::Approved): User
{
    return User::factory()->create(['type' => UserType::Sp, 'status' => $status]);
}

// ---------------------------------------------------------------- slugs

it('generates a slug on create and suffixes collisions', function () {
    $a = signupPlan(['name' => 'Premium Plan']);
    $b = signupPlan(['name' => 'Premium Plan']);

    expect($a->slug)->toBe('premium-plan')
        ->and($b->slug)->toBe('premium-plan-2');
});

it('keeps slugs globally unique when a reseller creates a plan', function () {
    signupPlan(['name' => 'Basic']); // admin plan

    $reseller = signupReseller();
    $this->actingAs($reseller, 'sp');

    $resellerPlan = signupPlan(['name' => 'Basic', 'sp_id' => $reseller->id]);

    expect($resellerPlan->slug)->toBe('basic-2');
});

it('saves the old slug as an alias and redirects it to the new one', function () {
    $plan = signupPlan(['name' => 'Premium']);
    $plan->update(['slug' => 'premium-pro']);

    expect(SignupSlugAlias::where('slug', 'premium')->value('tariff_plan_id'))->toEqual($plan->id);

    $this->get('/register/premium')
        ->assertRedirect(route('register.slug', ['slug' => 'premium-pro']));
});

it('lets a plan revert to its own old slug and drops that alias', function () {
    $plan = signupPlan(['name' => 'Premium']);
    $plan->update(['slug' => 'premium-pro']);

    expect(TariffSlugService::isAliasTaken('premium', $plan->id))->toBeFalse()
        ->and(TariffSlugService::isAliasTaken('premium'))->toBeTrue();

    $plan->update(['slug' => 'premium']);

    expect(SignupSlugAlias::where('slug', 'premium')->exists())->toBeFalse()
        ->and(SignupSlugAlias::where('slug', 'premium-pro')->exists())->toBeTrue();
});

it('does not let another plan take a slug that is an alias', function () {
    $plan = signupPlan(['name' => 'Premium']);
    $plan->update(['slug' => 'premium-pro']);

    $other = signupPlan(['name' => 'Other']);

    expect(TariffSlugService::isTaken('premium', $other->id))->toBeTrue()
        ->and(signupPlan(['name' => 'Premium'])->slug)->toBe('premium-2');
});

it('reserves the slug after a plan is deleted', function () {
    $plan = signupPlan(['name' => 'Basic']);
    $plan->delete();

    expect(TariffSlugService::isTaken('basic'))->toBeTrue();
    $this->get('/register/basic')->assertStatus(410);
    expect(signupPlan(['name' => 'Basic'])->slug)->toBe('basic-2');
});

// ---------------------------------------------------------------- public route

it('stores the slug and hands off to the register page for an active admin plan', function () {
    signupPlan(['name' => 'Basic']);

    $this->get('/register/basic')
        ->assertRedirect('/register')
        ->assertSessionHas(TariffSlugService::SESSION_KEY, 'basic');
});

it('hands off for an active reseller plan', function () {
    $reseller = signupReseller();
    signupPlan(['name' => 'Retail', 'sp_id' => $reseller->id]);

    $this->get('/register/retail')
        ->assertRedirect('/register')
        ->assertSessionHas(TariffSlugService::SESSION_KEY, 'retail');
});

it('returns 410 for an unknown slug', function () {
    $this->get('/register/does-not-exist')->assertStatus(410);
});

it('returns 410 for plans that are not active', function (TariffPlanStatus $status) {
    signupPlan(['name' => 'Basic', 'status' => $status]);

    $this->get('/register/basic')->assertStatus(410);
})->with([TariffPlanStatus::Draft, TariffPlanStatus::Archived]);

it('returns 410 when the reseller is not approved', function (UserStatus $status) {
    $reseller = signupReseller($status);
    signupPlan(['name' => 'Retail', 'sp_id' => $reseller->id]);

    $this->get('/register/retail')->assertStatus(410);
})->with([UserStatus::Pending, UserStatus::Rejected, UserStatus::Banned]);

it('returns 410 when the reseller was soft-deleted', function () {
    $reseller = signupReseller();
    signupPlan(['name' => 'Retail', 'sp_id' => $reseller->id]);
    $reseller->delete();

    $this->get('/register/retail')->assertStatus(410);
});

it('still serves the plain register page', function () {
    $this->get('/register')->assertOk();
});

// ---------------------------------------------------------------- registration (adapt as needed)

function signupRegistrationData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Test Customer',
        'email' => 'signup@example.com',
        'phone' => '+8801711111111',
        'password' => 'Str0ng!Passw0rd#1',
        'passwordConfirmation' => 'Str0ng!Passw0rd#1',
        'nid_number' => '1234567890',
        'front_nid' => UploadedFile::fake()->image('front.jpg'),
        'back_nid' => UploadedFile::fake()->image('back.jpg'),
        'live_photo' => 'data:image/jpeg;base64,'.base64_encode('fake'),
        'business_name' => 'Acme Ltd',
        'trade_license_number' => 'TL-1',
        'business_address' => 'Dhaka',
        'trade_license_file' => UploadedFile::fake()->create('license.pdf', 100, 'application/pdf'),
        'terms' => true,
    ], $overrides);
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('customer'));
    Storage::fake('private');
    Event::fake([Registered::class]);

    $this->mock(OtpVerificationService::class, function ($mock) {
        $mock->shouldReceive('isVerified')->andReturn(true);
        $mock->shouldReceive('normalizePhoneNumber')->andReturnUsing(fn ($phone) => $phone);
    });
});

it('assigns the admin plan after registering through its link', function () {
    $plan = signupPlan(['name' => 'Premium']);
    session([TariffSlugService::SESSION_KEY => 'premium']);

    Livewire::test(Register::class)
        ->fillForm(signupRegistrationData())
        ->call('register')
        ->assertHasNoFormErrors();

    $user = User::withoutGlobalScopes()->where('email', 'signup@example.com')->firstOrFail();
    $assignment = TariffPlanAssignment::where('user_id', $user->id)->firstOrFail();

    expect($assignment->tariff_plan_id)->toEqual($plan->id)
        ->and($assignment->status)->toBe(TariffPlanAssignmentStatus::Active)
        ->and($user->sp_id)->toBeNull();
});

it('places the customer under the reseller for a reseller plan', function () {
    $reseller = signupReseller();
    $plan = signupPlan(['name' => 'Retail', 'sp_id' => $reseller->id]);
    session([TariffSlugService::SESSION_KEY => 'retail']);

    Livewire::test(Register::class)
        ->fillForm(signupRegistrationData())
        ->call('register')
        ->assertHasNoFormErrors();

    $user = User::withoutGlobalScopes()->where('email', 'signup@example.com')->firstOrFail();

    expect($user->sp_id)->toEqual($reseller->id)
        ->and(TariffPlanAssignment::where('user_id', $user->id)->value('tariff_plan_id'))->toEqual($plan->id);
});

it('ignores a tampered tariff_plan_id in the submitted data', function () {
    $plan = signupPlan(['name' => 'Premium']);
    $other = signupPlan(['name' => 'Enterprise']);
    session([TariffSlugService::SESSION_KEY => 'premium']);

    Livewire::test(Register::class)
        ->fillForm(signupRegistrationData())
        ->set('data.tariff_plan_id', $other->id)
        ->set('data.sp_id', 999)
        ->call('register');

    $user = User::withoutGlobalScopes()->where('email', 'signup@example.com')->firstOrFail();

    expect(TariffPlanAssignment::where('user_id', $user->id)->value('tariff_plan_id'))->toEqual($plan->id)
        ->and($user->sp_id)->toBeNull();
});

it('does not let the browser change the locked plan slug', function () {
    signupPlan(['name' => 'Premium']);
    signupPlan(['name' => 'Enterprise']);
    session([TariffSlugService::SESSION_KEY => 'premium']);

    expect(fn () => Livewire::test(Register::class)->set('signupPlanSlug', 'enterprise'))
        ->toThrow(Exception::class);
});

it('rejects the submit and creates no account when the plan was deactivated after page load', function () {
    $plan = signupPlan(['name' => 'Premium']);
    session([TariffSlugService::SESSION_KEY => 'premium']);

    $component = Livewire::test(Register::class)->fillForm(signupRegistrationData());

    $plan->update(['status' => TariffPlanStatus::Archived]);

    $component->call('register');

    expect(User::withoutGlobalScopes()->where('email', 'signup@example.com')->exists())->toBeFalse();
});

it('registers normally with no plan when there is no signup link', function () {
    Livewire::test(Register::class)
        ->fillForm(signupRegistrationData())
        ->call('register')
        ->assertHasNoFormErrors();

    $user = User::withoutGlobalScopes()->where('email', 'signup@example.com')->firstOrFail();

    expect(TariffPlanAssignment::where('user_id', $user->id)->exists())->toBeFalse();
});
