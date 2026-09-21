<?php

use App\Enums\TariffPlanAssignmentStatus;
use App\Enums\TariffPlanStatus;
use App\Enums\UserType;
use App\Filament\Admin\Resources\Users\Customers\Pages\EditCustomer;
use App\Filament\Admin\Resources\Users\Customers\Pages\ListCustomers;
use App\Models\TariffPlan;
use App\Models\TariffPlanAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('admin can assign and update tariff plan on customer edit page', function () {
    $admin = User::factory()->create(['type' => UserType::Superman]);
    $customer = User::factory()->create(['type' => UserType::Customer]);
    $planA = TariffPlan::create(['name' => 'Standard Rate Plan', 'type' => 'custom', 'status' => TariffPlanStatus::Active, 'created_by' => $admin->id]);
    $planB = TariffPlan::create(['name' => 'Premium Rate Plan', 'type' => 'custom', 'status' => TariffPlanStatus::Active, 'created_by' => $admin->id]);

    $this->actingAs($admin);

    // Initial assignment of Plan A
    Livewire::test(EditCustomer::class, ['record' => $customer->id])
        ->fillForm([
            'tariff_plan_id' => $planA->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($customer->refresh()->activeTariffAssignment)->not->toBeNull()
        ->and($customer->activeTariffAssignment->tariff_plan_id)->toBe($planA->id);

    // Update assignment to Plan B
    Livewire::test(EditCustomer::class, ['record' => $customer->id])
        ->fillForm([
            'tariff_plan_id' => $planB->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($customer->refresh()->activeTariffAssignment->tariff_plan_id)->toBe($planB->id)
        ->and(TariffPlanAssignment::where('user_id', $customer->id)->where('status', TariffPlanAssignmentStatus::Superseded)->count())->toBe(1);
});

test('customers table renders current active plan', function () {
    $admin = User::factory()->create(['type' => UserType::Superman]);
    $customer = User::factory()->create(['type' => UserType::Customer]);
    $plan = TariffPlan::create(['name' => 'Special Enterprise Plan', 'type' => 'custom', 'status' => TariffPlanStatus::Active, 'created_by' => $admin->id]);

    TariffPlanAssignment::create([
        'user_id' => $customer->id,
        'tariff_plan_id' => $plan->id,
        'assigned_by' => $admin->id,
        'effective_from' => now(),
        'status' => TariffPlanAssignmentStatus::Active->value,
    ]);

    $this->actingAs($admin);

    Livewire::test(ListCustomers::class)
        ->assertCanSeeTableRecords([$customer]);
});
