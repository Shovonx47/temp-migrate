<?php

use App\Filament\Customer\Resources\Audience\Groups\GroupResource;
use App\Filament\Customer\Resources\Audience\Groups\Pages\CreateGroup;
use App\Filament\Customer\Resources\Audience\Groups\Pages\EditGroup;
use App\Filament\Customer\Resources\Audience\Groups\Pages\ListGroups;
use App\Filament\Customer\Resources\Audience\Groups\Pages\ViewGroup;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

test('customer can list their groups', function () {
    $user = User::factory()->create();
    $groups = Group::factory()->for($user)->count(3)->create();

    livewire(ListGroups::class, ['resource' => GroupResource::class])
        ->actingAs($user)
        ->assertCanSeeTableRecords($groups);
});

test('customer can view a group', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user)->create();

    livewire(ViewGroup::class, ['record' => $group->id, 'resource' => GroupResource::class])
        ->actingAs($user)
        ->assertSee($group->name);
});

test('customer can create a group', function () {
    $user = User::factory()->create();

    livewire(CreateGroup::class, ['resource' => GroupResource::class])
        ->actingAs($user)
        ->fillForm(['name' => 'Test Group'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Group::where('user_id', $user->id)->where('name', 'Test Group')->exists())->toBeTrue();
});

test('customer can edit their group', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user)->create(['name' => 'Original']);

    livewire(EditGroup::class, ['record' => $group->id, 'resource' => GroupResource::class])
        ->actingAs($user)
        ->fillForm(['name' => 'Updated'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($group->refresh()->name)->toBe('Updated');
});

test('customer cannot view groups of another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $group = Group::factory()->for($other)->create();

    livewire(ViewGroup::class, ['record' => $group->id, 'resource' => GroupResource::class])
        ->actingAs($user)
        ->assertForbidden();
});
