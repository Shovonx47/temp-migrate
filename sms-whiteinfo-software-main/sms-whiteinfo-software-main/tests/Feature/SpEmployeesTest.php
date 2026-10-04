<?php

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Filament\Admin\Pages\Resellers\ResellerEmployeesPage;
use App\Filament\Admin\Resources\Users\Sps\Pages\EditSp;
use App\Filament\Admin\Resources\Users\Sps\RelationManagers\EmployeesRelationManager;
use App\Filament\Sp\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Sp\Resources\Customers\Pages\ListCustomers;
use App\Filament\Sp\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Sp\Resources\Employees\Pages\EditEmployee;
use App\Filament\Sp\Resources\Employees\Pages\ListEmployees;
use App\Filament\Sp\Resources\Employees\Pages\ViewEmployee;
use App\Models\SpRole;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('parent sp can list and view their own employees only', function () {
    Filament::setCurrentPanel(Filament::getPanel('sp'));

    $sp1 = User::factory()->sp()->create(['name' => 'SP One']);
    $sp2 = User::factory()->sp()->create(['name' => 'SP Two']);

    $employee1 = User::factory()->employeeOf($sp1)->create(['name' => 'Emp One']);
    $employee2 = User::factory()->employeeOf($sp2)->create(['name' => 'Emp Two']);

    actingAs($sp1, 'sp');

    Livewire::test(ListEmployees::class)
        ->assertCanSeeTableRecords([$employee1])
        ->assertCanNotSeeTableRecords([$employee2]);
});

test('parent sp can create an employee', function () {
    Filament::setCurrentPanel(Filament::getPanel('sp'));

    $sp = User::factory()->sp()->create();
    actingAs($sp, 'sp');

    Livewire::test(CreateEmployee::class)
        ->fillForm([
            'name' => 'New SP Staff',
            'email' => 'staff@sp.com',
            'phone' => '+8801712345678',
            'password' => 'password123',
            'status' => UserStatus::Approved,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $employee = User::query()->where('email', 'staff@sp.com')->first();
    expect($employee)->not->toBeNull();
    expect($employee->type)->toBe(UserType::Sp);
    expect($employee->sp_id)->toBe($sp->id);
    expect($employee->balance)->toBe(0.0);
});

test('parent sp can edit their employee', function () {
    Filament::setCurrentPanel(Filament::getPanel('sp'));

    $sp = User::factory()->sp()->create();
    $employee = User::factory()->employeeOf($sp)->create(['name' => 'Old Name']);
    actingAs($sp, 'sp');

    Livewire::test(EditEmployee::class, ['record' => $employee->id])
        ->fillForm([
            'name' => 'Updated Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($employee->fresh()->name)->toBe('Updated Name');
});

test('parent sp can view their employee details', function () {
    Filament::setCurrentPanel(Filament::getPanel('sp'));

    $sp = User::factory()->sp()->create();
    $employee = User::factory()->employeeOf($sp)->create(['name' => 'Staff Member']);
    actingAs($sp, 'sp');

    Livewire::test(ViewEmployee::class, ['record' => $employee->id])
        ->assertSuccessful();
});

test('sp employee can see parent sp customers and create new customers under parent sp', function () {
    Filament::setCurrentPanel(Filament::getPanel('sp'));

    $sp = User::factory()->sp()->create(['name' => 'Parent SP']);
    $role = SpRole::create([
        'scope' => (string) $sp->id,
        'name' => 'Sales',
        'permissions' => ['customers.view', 'customers.create'],
    ]);
    $employee = User::factory()->employeeOf($sp)->create(['sp_role_id' => $role->id]);

    $customer1 = User::factory()->customerOf($sp)->create(['name' => 'Customer A']);
    $otherSp = User::factory()->sp()->create();
    $customerOther = User::factory()->customerOf($otherSp)->create(['name' => 'Customer Other']);

    actingAs($employee, 'sp');

    Livewire::test(ListCustomers::class)
        ->assertCanSeeTableRecords([$customer1])
        ->assertCanNotSeeTableRecords([$customerOther]);

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'Customer Added By Employee',
            'email' => 'emp_cust@example.com',
            'phone' => '+8801812345678',
            'password' => 'password123',
            'status' => UserStatus::Approved,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $newCust = User::query()->where('email', 'emp_cust@example.com')->first();
    expect($newCust)->not->toBeNull();
    expect($newCust->type)->toBe(UserType::Customer);
    expect($newCust->sp_id)->toBe($sp->id);
});

test('superman admin can view sp employees on reseller employees page', function () {
    Filament::setCurrentPanel(Filament::getPanel('superman'));

    $admin = User::factory()->superman()->create();
    $sp = User::factory()->sp()->create(['name' => 'Top SP']);
    $employee = User::factory()->employeeOf($sp)->create(['name' => 'Staff 1']);

    actingAs($admin, 'superman');

    Livewire::test(ResellerEmployeesPage::class)
        ->assertCanSeeTableRecords([$employee]);
});

test('superman admin can view employees relation manager on sp resource', function () {
    Filament::setCurrentPanel(Filament::getPanel('superman'));

    $admin = User::factory()->superman()->create();
    $sp = User::factory()->sp()->create(['name' => 'Top SP']);
    $employee = User::factory()->employeeOf($sp)->create(['name' => 'Staff In Relation']);

    actingAs($admin, 'superman');

    Livewire::test(EmployeesRelationManager::class, [
        'ownerRecord' => $sp,
        'pageClass' => EditSp::class,
    ])
        ->assertCanSeeTableRecords([$employee]);
});
