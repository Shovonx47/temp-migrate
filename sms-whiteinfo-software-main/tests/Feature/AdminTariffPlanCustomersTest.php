<?php

use App\Enums\TariffPlanAssignmentStatus;
use App\Enums\TariffPlanStatus;
use App\Enums\UserType;
use App\Filament\Admin\Resources\Billing\TariffPlans\Pages\AssignedCustomers;
use App\Filament\Admin\Resources\Billing\TariffPlans\Pages\ListTariffPlans;
use App\Filament\Admin\Resources\Billing\TariffPlans\TariffPlanResource;
use App\Models\TariffPlan;
use App\Models\TariffPlanAssignment;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('superman'));
    $this->admin = User::factory()->superman()->create();
    actingAs($this->admin, 'superman');
    $this->plan = TariffPlan::create([
        'name' => 'Shahin Tariff',
        'type' => 'custom',
        'status' => TariffPlanStatus::Active,
        'created_by' => $this->admin->id,
    ]);
});

afterEach(function () {
    Filament::setCurrentPanel(null);
});

test('the customer count links to an active customer assignment table', function () {
    $assignedCustomer = User::factory()->create(['type' => UserType::Customer]);
    $formerCustomer = User::factory()->create(['type' => UserType::Customer]);
    $otherPlanCustomer = User::factory()->create(['type' => UserType::Customer]);
    $otherPlan = TariffPlan::create([
        'name' => 'Other Tariff',
        'type' => 'standard',
        'status' => TariffPlanStatus::Active,
        'created_by' => $this->admin->id,
    ]);

    $assigned = TariffPlanAssignment::create([
        'user_id' => $assignedCustomer->id,
        'tariff_plan_id' => $this->plan->id,
        'assigned_by' => $this->admin->id,
        'effective_from' => now()->subDay(),
        'status' => TariffPlanAssignmentStatus::Active,
        'notes' => 'Current enterprise pricing',
    ]);
    $superseded = TariffPlanAssignment::create([
        'user_id' => $formerCustomer->id,
        'tariff_plan_id' => $this->plan->id,
        'assigned_by' => $this->admin->id,
        'effective_from' => now()->subMonths(2),
        'status' => TariffPlanAssignmentStatus::Superseded,
    ]);
    $otherPlanAssignment = TariffPlanAssignment::create([
        'user_id' => $otherPlanCustomer->id,
        'tariff_plan_id' => $otherPlan->id,
        'assigned_by' => $this->admin->id,
        'effective_from' => now(),
        'status' => TariffPlanAssignmentStatus::Active,
    ]);

    $customersUrl = TariffPlanResource::getUrl('assigned-customers', ['record' => $this->plan]);

    Livewire::test(ListTariffPlans::class)
        ->assertSee('1');

    $this->get(TariffPlanResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSee($customersUrl, false);

    Livewire::test(AssignedCustomers::class, ['record' => $this->plan->id])
        ->assertSee('1 active customer')
        ->assertCanSeeTableRecords([$assigned])
        ->assertCanNotSeeTableRecords([$superseded, $otherPlanAssignment])
        ->assertSee('Current enterprise pricing');

    expect($customersUrl)
        ->toContain("/tariff-plans/{$this->plan->id}/customers");
});
