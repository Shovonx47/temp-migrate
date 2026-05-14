<?php

use App\Filament\Customer\Resources\Audience\Contacts\ContactResource;
use App\Filament\Customer\Resources\Audience\Contacts\Pages\CreateContact;
use App\Filament\Customer\Resources\Audience\Contacts\Pages\EditContact;
use App\Filament\Customer\Resources\Audience\Contacts\Pages\ListContacts;
use App\Filament\Customer\Resources\Audience\Contacts\Pages\ViewContact;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

test('customer can list their contacts', function () {
    $user = User::factory()->create();
    $contacts = Contact::factory()->for($user)->count(3)->create();

    livewire(ListContacts::class, ['resource' => ContactResource::class])
        ->actingAs($user)
        ->assertCanSeeTableRecords($contacts);
});

test('customer can view a contact', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->for($user)->create();

    livewire(ViewContact::class, ['record' => $contact->id, 'resource' => ContactResource::class])
        ->actingAs($user)
        ->assertSee($contact->name);
});

test('customer can create a contact', function () {
    $user = User::factory()->create();

    livewire(CreateContact::class, ['resource' => ContactResource::class])
        ->actingAs($user)
        ->fillForm([
            'name' => 'Test Contact',
            'phone' => '+8801700000001',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Contact::where('user_id', $user->id)->where('name', 'Test Contact')->exists())->toBeTrue();
});

test('customer can edit their contact', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->for($user)->create(['name' => 'Original']);

    livewire(EditContact::class, ['record' => $contact->id, 'resource' => ContactResource::class])
        ->actingAs($user)
        ->fillForm(['name' => 'Updated'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($contact->refresh()->name)->toBe('Updated');
});

test('customer cannot view contacts of another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $contact = Contact::factory()->for($other)->create();

    livewire(ViewContact::class, ['record' => $contact->id, 'resource' => ContactResource::class])
        ->actingAs($user)
        ->assertForbidden();
});
