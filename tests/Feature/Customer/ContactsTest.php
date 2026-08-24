<?php

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Filament\Customer\Resources\Audience\Contacts\ContactResource;
use App\Filament\Customer\Resources\Audience\Contacts\Pages\CreateContact;
use App\Filament\Customer\Resources\Audience\Contacts\Pages\EditContact;
use App\Filament\Customer\Resources\Audience\Contacts\Pages\ListContacts;
use App\Filament\Customer\Resources\Audience\Contacts\Pages\ViewContact;
use App\Models\Contact;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('customer'));

    $this->user = User::factory()->create([
        'type' => UserType::Customer,
        'status' => UserStatus::Approved,
        'phone' => '+8801711111111',
    ]);

    actingAs($this->user, 'customer');
});

test('customer can list their contacts', function () {
    $contacts = Contact::factory()->for($this->user)->count(3)->create();

    Livewire::test(ListContacts::class, ['resource' => ContactResource::class])
        ->assertCanSeeTableRecords($contacts);
});

test('customer can view a contact', function () {
    $contact = Contact::factory()->for($this->user)->create();

    Livewire::test(ViewContact::class, ['record' => $contact->id, 'resource' => ContactResource::class])
        ->assertSee($contact->name);
});

test('customer can create a contact with name and phone only', function () {
    Livewire::test(CreateContact::class, ['resource' => ContactResource::class])
        ->fillForm([
            'name' => 'Test Contact',
            'phone' => '01712345678',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Contact::where('user_id', $this->user->id)->where('name', 'Test Contact')->exists())->toBeTrue();
});

test('customer can edit their contact', function () {
    $contact = Contact::factory()->for($this->user)->create(['name' => 'Original']);

    Livewire::test(EditContact::class, ['record' => $contact->id, 'resource' => ContactResource::class])
        ->fillForm(['name' => 'Updated'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($contact->refresh()->name)->toBe('Updated');
});

test('customer cannot view contacts of another user', function () {
    $other = User::factory()->create(['type' => UserType::Customer, 'status' => UserStatus::Approved]);
    $contact = Contact::factory()->for($other)->create();

    expect(fn () => Livewire::test(ViewContact::class, ['record' => $contact->id, 'resource' => ContactResource::class]))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});
