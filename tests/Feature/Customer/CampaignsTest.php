<?php

use App\Filament\Customer\Resources\Messaging\Campaigns\CampaignResource;
use App\Filament\Customer\Resources\Messaging\Campaigns\Pages\CreateCampaign;
use App\Filament\Customer\Resources\Messaging\Campaigns\Pages\EditCampaign;
use App\Filament\Customer\Resources\Messaging\Campaigns\Pages\ListCampaigns;
use App\Filament\Customer\Resources\Messaging\Campaigns\Pages\ViewCampaign;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

test('customer can list their campaigns', function () {
    $user = User::factory()->create();
    $campaigns = Campaign::factory()->for($user)->count(3)->create();

    livewire(ListCampaigns::class, ['resource' => CampaignResource::class])
        ->actingAs($user)
        ->assertCanSeeTableRecords($campaigns);
});

test('customer can view a campaign', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->for($user)->create();

    livewire(ViewCampaign::class, ['record' => $campaign->id, 'resource' => CampaignResource::class])
        ->actingAs($user)
        ->assertSee($campaign->name);
});

test('customer can create a campaign', function () {
    $user = User::factory()->create();

    livewire(CreateCampaign::class, ['resource' => CampaignResource::class])
        ->actingAs($user)
        ->fillForm(['name' => 'Test Campaign'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Campaign::where('user_id', $user->id)->where('name', 'Test Campaign')->exists())->toBeTrue();
});

test('customer can edit their campaign', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->for($user)->create(['name' => 'Original']);

    livewire(EditCampaign::class, ['record' => $campaign->id, 'resource' => CampaignResource::class])
        ->actingAs($user)
        ->fillForm(['name' => 'Updated'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($campaign->refresh()->name)->toBe('Updated');
});

test('customer cannot view campaigns of another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $campaign = Campaign::factory()->for($other)->create();

    livewire(ViewCampaign::class, ['record' => $campaign->id, 'resource' => CampaignResource::class])
        ->actingAs($user)
        ->assertForbidden();
});
