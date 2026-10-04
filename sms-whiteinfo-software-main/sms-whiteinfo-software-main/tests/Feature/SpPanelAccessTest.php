<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sp can access sp panel only', function () {
    $sp = User::factory()->sp()->create();

    expect($sp->canAccessPanel(Filament::getPanel('sp')))->toBeTrue()
        ->and($sp->canAccessPanel(Filament::getPanel('superman')))->toBeFalse()
        ->and($sp->canAccessPanel(Filament::getPanel('customer')))->toBeFalse();

    $this->actingAs($sp, 'sp')
        ->get('/sp')
        ->assertSuccessful();
});

test('customer cannot access sp panel', function () {
    $customer = User::factory()->approved()->create();

    expect($customer->canAccessPanel(Filament::getPanel('sp')))->toBeFalse();
});

test('superman cannot access sp panel', function () {
    $admin = User::factory()->superman()->create();

    expect($admin->canAccessPanel(Filament::getPanel('sp')))->toBeFalse();
});
