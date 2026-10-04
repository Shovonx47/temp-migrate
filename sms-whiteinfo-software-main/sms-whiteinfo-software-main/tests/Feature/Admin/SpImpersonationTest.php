<?php

use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Resources\Users\Sps\Actions\LoginAction;
use App\Filament\Admin\Resources\Users\Sps\Pages\ListSps;
use App\Filament\Admin\Resources\Users\Sps\SpResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use STS\FilamentImpersonate\Facades\Impersonation;

uses(RefreshDatabase::class);

test('user canBeImpersonated allows both customer and sp', function () {
    $customer = User::factory()->create();
    $sp = User::factory()->sp()->create();
    $superman = User::factory()->superman()->create();

    expect($customer->canBeImpersonated())->toBeTrue()
        ->and($sp->canBeImpersonated())->toBeTrue()
        ->and($superman->canBeImpersonated())->toBeFalse();
});

test('sp resource belongs to user management navigation group', function () {
    expect(SpResource::getNavigationGroup())->toBe(SupermanNavigationGroup::User);
});

test('admin can view sp list in superman panel', function () {
    $admin = User::factory()->superman()->create();
    $sp = User::factory()->sp()->create([
        'name' => 'Demo Reseller SP',
        'email' => 'sp@test.com',
    ]);

    Filament::setCurrentPanel(Filament::getPanel('superman'));
    $this->actingAs($admin, 'superman');

    Livewire::test(ListSps::class)
        ->assertCanSeeTableRecords([$sp]);
});

test('sp login action is configured with sp guard and sp panel redirect', function () {
    $sp = User::factory()->sp()->create();
    $action = LoginAction::make();

    $guard = $action->evaluate(fn () => $action->getGuard(), ['record' => $sp]);
    expect($guard)->toBe('sp');

    $redirectTo = $action->evaluate(fn () => $action->getRedirectTo(), ['record' => $sp]);
    expect($redirectTo)->toBe(Filament::getPanel('sp')->getUrl());
});

test('admin can impersonate sp and access sp panel', function () {
    $admin = User::factory()->superman()->create();
    $sp = User::factory()->sp()->create();

    Filament::setCurrentPanel(Filament::getPanel('superman'));
    $this->actingAs($admin, 'superman');

    // Enter impersonation
    $entered = Impersonation::enter($admin, $sp, 'sp');
    expect($entered)->toBeTrue();
    expect(Impersonation::isImpersonating())->toBeTrue();

    // Impersonated SP can access the SP panel
    $this->get('/sp')->assertSuccessful();

    // Leaving impersonation redirects back
    $response = $this->get(route('filament-impersonate.leave'));
    $response->assertRedirect();
    expect(Impersonation::isImpersonating())->toBeFalse();
});
