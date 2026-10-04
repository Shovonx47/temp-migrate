<?php

declare(strict_types=1);

use App\Enums\SpPermission;
use App\Enums\SupermanPermission;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Filament\Sp\Resources\Customers\CustomerResource;
use App\Filament\Sp\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Sp\Resources\Roles\Pages\CreateRole;
use App\Filament\Sp\Resources\Roles\Pages\ListRoles;
use App\Http\Middleware\RequireSpPermission;
use App\Models\SpRole;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('sp owner can create a custom role with permissions scoped to their reseller', function () {
    Filament::setCurrentPanel(Filament::getPanel('sp'));

    $sp = User::factory()->sp()->create(['name' => 'ABC Telecom']);
    actingAs($sp, 'sp');

    Livewire::test(CreateRole::class)
        ->fillForm([
            'name' => 'Sales Executive',
            'description' => 'Can view, create and edit customers',
            'permissions' => [
                SpPermission::CustomersView->value,
                SpPermission::CustomersCreate->value,
                SpPermission::CustomersEdit->value,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $role = SpRole::where('name', 'Sales Executive')->first();
    expect($role)->not->toBeNull()
        ->and($role->scope)->toBe((string) $sp->id)
        ->and($role->permissions)->toContain(SpPermission::CustomersView->value)
        ->and($role->permissions)->not->toContain(SpPermission::CustomersDelete->value)
        ->and($role->permissions)->not->toContain(SpPermission::BillingManage->value);
});

test('sp roles table only shows roles belonging to the current reseller', function () {
    Filament::setCurrentPanel(Filament::getPanel('sp'));

    $sp1 = User::factory()->sp()->create();
    $sp2 = User::factory()->sp()->create();

    $role1 = SpRole::create([
        'scope' => (string) $sp1->id,
        'name' => 'Role SP 1',
        'permissions' => [SpPermission::CustomersView->value],
    ]);

    $role2 = SpRole::create([
        'scope' => (string) $sp2->id,
        'name' => 'Role SP 2',
        'permissions' => [SpPermission::CustomersView->value],
    ]);

    actingAs($sp1, 'sp');

    Livewire::test(ListRoles::class)
        ->assertCanSeeTableRecords([$role1])
        ->assertCanNotSeeTableRecords([$role2]);
});

test('sp owner can assign a role to an employee and permissions take effect', function () {
    Filament::setCurrentPanel(Filament::getPanel('sp'));

    $sp = User::factory()->sp()->create(['name' => 'ABC Telecom']);

    $role = SpRole::create([
        'scope' => (string) $sp->id,
        'name' => 'Sales Executive',
        'permissions' => [
            SpPermission::CustomersView->value,
            SpPermission::CustomersCreate->value,
            SpPermission::CustomersEdit->value,
        ],
    ]);

    actingAs($sp, 'sp');

    Livewire::test(CreateEmployee::class)
        ->fillForm([
            'name' => 'Rahim',
            'email' => 'rahim@abctelecom.com',
            'phone' => '+8801711223344',
            'sp_role_id' => $role->id,
            'status' => UserStatus::Approved,
            'password' => 'secret123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $employee = User::where('email', 'rahim@abctelecom.com')->first();
    expect($employee)->not->toBeNull()
        ->and($employee->sp_role_id)->toBe($role->id)
        ->and($employee->isSpEmployee())->toBeTrue();

    // Verify backend permission check
    expect($employee->hasSpPermission('customers.view'))->toBeTrue()
        ->and($employee->hasSpPermission('customers.create'))->toBeTrue()
        ->and($employee->hasSpPermission('customers.edit'))->toBeTrue()
        ->and($employee->hasSpPermission('customers.delete'))->toBeFalse()
        ->and($employee->hasSpPermission('billing.manage'))->toBeFalse();
});

test('sp employee cannot delete customers when customers.delete permission is missing', function () {
    $sp = User::factory()->sp()->create(['name' => 'ABC Telecom']);
    $role = SpRole::create([
        'scope' => (string) $sp->id,
        'name' => 'Sales Executive',
        'permissions' => [
            SpPermission::CustomersView->value,
            SpPermission::CustomersCreate->value,
        ],
    ]);

    $employee = User::factory()->employeeOf($sp)->create([
        'sp_role_id' => $role->id,
    ]);

    $customer = User::factory()->customerOf($sp)->create();

    actingAs($employee, 'sp');

    expect(CustomerResource::canViewAny())->toBeTrue()
        ->and(CustomerResource::canCreate())->toBeTrue()
        ->and(CustomerResource::canDelete($customer))->toBeFalse()
        ->and(CustomerResource::canEdit($customer))->toBeFalse();
});

test('sp owner has full permissions without requiring an assigned role', function () {
    $sp = User::factory()->sp()->create(['sp_id' => null, 'sp_role_id' => null]);
    $customer = User::factory()->customerOf($sp)->create();

    actingAs($sp, 'sp');

    expect($sp->isSpOwner())->toBeTrue()
        ->and($sp->hasSpPermission('customers.view'))->toBeTrue()
        ->and($sp->hasSpPermission('customers.delete'))->toBeTrue()
        ->and($sp->hasSpPermission('billing.manage'))->toBeTrue()
        ->and(CustomerResource::canDelete($customer))->toBeTrue();
});

test('cross reseller data isolation prevents employee of one reseller from seeing another resellers data', function () {
    $spA = User::factory()->sp()->create(['name' => 'ABC Telecom']);
    $spB = User::factory()->sp()->create(['name' => 'XYZ Telecom']);

    $roleA = SpRole::create([
        'scope' => (string) $spA->id,
        'name' => 'Sales',
        'permissions' => [SpPermission::CustomersView->value],
    ]);

    $employeeA = User::factory()->employeeOf($spA)->create(['sp_role_id' => $roleA->id]);

    $customerA = User::factory()->customerOf($spA)->create(['name' => 'Customer of ABC']);
    $customerB = User::factory()->customerOf($spB)->create(['name' => 'Customer of XYZ']);

    actingAs($employeeA, 'sp');

    // Scoped query by employee should only return ABC customers
    $visibleCustomers = User::where('type', UserType::Customer)->get();
    expect($visibleCustomers->pluck('id'))->toContain($customerA->id)
        ->and($visibleCustomers->pluck('id'))->not->toContain($customerB->id);
});

test('super admin has all permissions implicitly without an assigned role', function () {
    $superman = User::factory()->superman()->create(['sp_role_id' => null]);

    actingAs($superman, 'superman');

    expect($superman->isSuperAdmin())->toBeTrue()
        ->and($superman->hasSpPermission('users.view'))->toBeTrue()
        ->and($superman->hasSpPermission('users.delete'))->toBeTrue()
        ->and($superman->hasSpPermission('resellers.manage'))->toBeTrue()
        ->and($superman->hasSpPermission('settings.manage'))->toBeTrue();
});

test('internal superman staff with assigned role only possesses granted permissions', function () {
    $role = SpRole::create([
        'scope' => 'superman',
        'name' => 'Support Staff',
        'permissions' => [
            SupermanPermission::UsersView->value,
            SupermanPermission::CustomersView->value,
        ],
    ]);

    $staff = User::factory()->superman()->create(['sp_role_id' => $role->id]);

    actingAs($staff, 'superman');

    expect($staff->isSuperAdmin())->toBeFalse()
        ->and($staff->hasSpPermission('users.view'))->toBeTrue()
        ->and($staff->hasSpPermission('customers.view'))->toBeTrue()
        ->and($staff->hasSpPermission('users.delete'))->toBeFalse()
        ->and($staff->hasSpPermission('settings.manage'))->toBeFalse();
});

test('require sp permission middleware aborts with 403 when user lacks permission', function () {
    $sp = User::factory()->sp()->create();
    $role = SpRole::create([
        'scope' => (string) $sp->id,
        'name' => 'Limited',
        'permissions' => [SpPermission::CustomersView->value],
    ]);

    $employee = User::factory()->employeeOf($sp)->create(['sp_role_id' => $role->id]);

    $middleware = app(RequireSpPermission::class);

    $request = Request::create('/sp/customers/delete/1', 'POST');
    $request->setUserResolver(fn () => $employee);

    expect(fn () => $middleware->handle($request, fn () => response('OK'), 'customers.delete'))
        ->toThrow(HttpException::class);
});

test('super admin can create a superman role in superman panel', function () {
    Filament::setCurrentPanel(Filament::getPanel('superman'));

    $superman = User::factory()->superman()->create();
    actingAs($superman, 'superman');

    Livewire::test(App\Filament\Admin\Resources\Roles\Pages\CreateRole::class)
        ->fillForm([
            'name' => 'Accountant',
            'description' => 'Billing and reports management',
            'permissions' => [
                SupermanPermission::BillingView->value,
                SupermanPermission::BillingManage->value,
                SupermanPermission::ReportsView->value,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $role = SpRole::where('name', 'Accountant')->first();
    expect($role)->not->toBeNull()
        ->and($role->scope)->toBe('superman')
        ->and($role->permissions)->toContain(SupermanPermission::BillingView->value)
        ->and($role->permissions)->not->toContain(SupermanPermission::UsersDelete->value);
});

test('super admin can create internal staff member in superman panel', function () {
    Filament::setCurrentPanel(Filament::getPanel('superman'));

    $superman = User::factory()->superman()->create();

    $role = SpRole::create([
        'scope' => 'superman',
        'name' => 'Support Manager',
        'permissions' => [
            SupermanPermission::UsersView->value,
            SupermanPermission::CustomersView->value,
        ],
    ]);

    actingAs($superman, 'superman');

    Livewire::test(App\Filament\Admin\Resources\Employees\Pages\CreateEmployee::class)
        ->fillForm([
            'name' => 'Support User',
            'email' => 'support.staff@example.com',
            'phone' => '+8801999888777',
            'sp_role_id' => $role->id,
            'status' => UserStatus::Approved,
            'password' => 'secret123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $staff = User::where('email', 'support.staff@example.com')->first();
    expect($staff)->not->toBeNull()
        ->and($staff->type)->toBe(UserType::Superman)
        ->and($staff->sp_role_id)->toBe($role->id)
        ->and($staff->isSuperAdmin())->toBeFalse();
});

test('sp employee with employees.view permission can see fellow employees of same sp', function () {
    $sp = User::factory()->sp()->create(['name' => 'ABC Telecom']);

    $managerRole = SpRole::create([
        'scope' => (string) $sp->id,
        'name' => 'Manager',
        'permissions' => [
            SpPermission::EmployeesView->value,
            SpPermission::CustomersView->value,
        ],
    ]);

    $manager = User::factory()->employeeOf($sp)->create(['sp_role_id' => $managerRole->id]);
    $coworker = User::factory()->employeeOf($sp)->create(['name' => 'Coworker']);

    $otherSp = User::factory()->sp()->create(['name' => 'Other SP']);
    $otherEmployee = User::factory()->employeeOf($otherSp)->create(['name' => 'Other Emp']);

    actingAs($manager, 'sp');

    $visibleEmployees = User::where('type', UserType::Sp)->whereNotNull('sp_id')->get();
    expect($visibleEmployees->pluck('id'))->toContain($coworker->id)
        ->and($visibleEmployees->pluck('id'))->not->toContain($otherEmployee->id);
});
