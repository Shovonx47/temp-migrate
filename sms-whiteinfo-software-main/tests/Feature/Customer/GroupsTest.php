<?php

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Filament\Customer\Resources\Audience\Groups\GroupResource;
use App\Filament\Customer\Resources\Audience\Groups\Pages\CreateGroup;
use App\Filament\Customer\Resources\Audience\Groups\Pages\EditGroup;
use App\Filament\Customer\Resources\Audience\Groups\Pages\ListGroups;
use App\Filament\Customer\Resources\Audience\Groups\Pages\ViewGroup;
use App\Filament\Customer\Resources\Audience\Groups\RelationManagers\ContactsRelationManager;
use App\Models\Contact;
use App\Models\Group;
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

test('customer can list their groups', function () {
    $groups = Group::factory()->for($this->user)->count(3)->create();

    Livewire::test(ListGroups::class, ['resource' => GroupResource::class])
        ->assertCanSeeTableRecords($groups);
});

test('customer can view a group', function () {
    $group = Group::factory()->for($this->user)->create();

    Livewire::test(ViewGroup::class, ['record' => $group->id, 'resource' => GroupResource::class])
        ->assertSee($group->name);
});

test('customer can create a group', function () {
    Livewire::test(CreateGroup::class, ['resource' => GroupResource::class])
        ->fillForm(['name' => 'Test Group'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Group::where('user_id', $this->user->id)->where('name', 'Test Group')->exists())->toBeTrue();
});

test('customer can edit their group', function () {
    $group = Group::factory()->for($this->user)->create(['name' => 'Original']);

    Livewire::test(EditGroup::class, ['record' => $group->id, 'resource' => GroupResource::class])
        ->fillForm(['name' => 'Updated'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($group->refresh()->name)->toBe('Updated');
});

test('customer cannot view groups of another user', function () {
    $other = User::factory()->create(['type' => UserType::Customer, 'status' => UserStatus::Approved]);
    $group = Group::factory()->for($other)->create();

    expect(fn () => Livewire::test(ViewGroup::class, ['record' => $group->id, 'resource' => GroupResource::class]))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

test('customer can manually add numbers to a group in relation manager', function () {
    $group = Group::factory()->for($this->user)->create(['name' => 'VIP Group']);

    Livewire::test(ContactsRelationManager::class, [
        'ownerRecord' => $group,
        'pageClass' => EditGroup::class,
    ])
        ->callTableAction('add_numbers', data: [
            'numbers' => "01711223344\n01811223344, 01911223344",
            'name' => 'VIP Member',
        ])
        ->assertHasNoTableActionErrors();

    expect($group->contacts()->count())->toBe(3);
});

test('customer can import contacts from CSV file into a group in relation manager', function () {
    \Illuminate\Support\Facades\Storage::fake('local');

    $group = Group::factory()->for($this->user)->create(['name' => 'Import Group']);

    $csvContent = "Name,Phone\nJohn Doe,01700112233\nJane Doe,01800112233\n";
    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);
    $storedPath = $file->store('uploads/group-imports', 'local');

    Livewire::test(ContactsRelationManager::class, [
        'ownerRecord' => $group,
        'pageClass' => EditGroup::class,
    ])
        ->callTableAction('import_file', data: [
            'file' => [$storedPath],
        ])
        ->assertHasNoTableActionErrors();

    expect($group->contacts()->count())->toBe(2);
});
