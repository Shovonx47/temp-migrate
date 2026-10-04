<?php

use App\Enums\TariffPlanStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Filament\Sp\Pages\Dashboard;
use App\Filament\Sp\Pages\Wallet;
use App\Filament\Sp\Resources\Billing\TariffPlans\Pages\CreateTariffPlan;
use App\Filament\Sp\Resources\Billing\TariffPlans\Pages\ListTariffPlans;
use App\Filament\Sp\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Sp\Resources\Customers\Pages\ListCustomers;
use App\Filament\Sp\Resources\Messaging\Senders\Pages\ListSenders;
use App\Filament\Sp\Resources\Messaging\Senders\Pages\ViewSender;
use App\Filament\Sp\Widgets\SpBalanceWidget;
use App\Filament\Sp\Widgets\SpStatsWidget;
use App\Models\Sender;
use App\Models\SpDistribution;
use App\Models\TariffPlan;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('sp'));

    $this->sp = User::factory()->sp()->create([
        'balance' => 5000,
        'status' => UserStatus::Approved,
        'phone' => '+8801710000001',
    ]);

    actingAs($this->sp, 'sp');
});

test('sp dashboard registers balance and stats widgets', function () {
    $widgets = (new Dashboard)->getWidgets();

    expect($widgets)->toContain(SpBalanceWidget::class, SpStatsWidget::class);
});

test('sp can list their customers only', function () {
    $ownCustomer = User::factory()->customerOf($this->sp)->create([
        'phone' => '+8801710000002',
    ]);
    $otherSp = User::factory()->sp()->create([
        'phone' => '+8801710000003',
    ]);
    $otherCustomer = User::factory()->customerOf($otherSp)->create([
        'phone' => '+8801710000004',
    ]);

    Livewire::test(ListCustomers::class)
        ->assertCanSeeTableRecords([$ownCustomer])
        ->assertCanNotSeeTableRecords([$otherCustomer]);
});

test('sp can create a customer under themselves', function () {
    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'SP Customer',
            'email' => 'sp-customer@example.com',
            'phone' => '+8801712345678',
            'status' => UserStatus::Approved,
            'password' => 'password',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $customer = User::query()
        ->where('email', 'sp-customer@example.com')
        ->first();

    expect($customer)->not->toBeNull()
        ->and($customer->type)->toBe(UserType::Customer)
        ->and($customer->sp_id)->toBe($this->sp->id);
});

test('sp can create a retail tariff plan scoped to themselves', function () {
    Livewire::test(CreateTariffPlan::class)
        ->fillForm([
            'name' => 'Retail Plan A',
            'type' => 'standard',
            'status' => TariffPlanStatus::Active,
            'currency' => 'BDT',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $plan = TariffPlan::query()->where('name', 'Retail Plan A')->first();

    expect($plan)->not->toBeNull()
        ->and($plan->sp_id)->toBe($this->sp->id)
        ->and($plan->created_by)->toBe($this->sp->id);
});

test('sp tariff plan list only shows their retail plans', function () {
    $ownPlan = TariffPlan::create([
        'name' => 'My Retail',
        'type' => 'standard',
        'status' => TariffPlanStatus::Active,
        'created_by' => $this->sp->id,
        'sp_id' => $this->sp->id,
    ]);

    $platformPlan = TariffPlan::withoutGlobalScopes()->create([
        'name' => 'Platform Wholesale',
        'type' => 'standard',
        'status' => TariffPlanStatus::Active,
        'created_by' => $this->sp->id,
        'sp_id' => null,
    ]);

    Livewire::test(ListTariffPlans::class)
        ->assertCanSeeTableRecords([$ownPlan])
        ->assertCanNotSeeTableRecords([$platformPlan]);
});

test('sp wallet page shows distribution history', function () {
    $customer = User::factory()->customerOf($this->sp)->create([
        'phone' => '+8801710000005',
    ]);

    $distribution = SpDistribution::withoutGlobalScopes()->create([
        'sp_id' => $this->sp->id,
        'customer_id' => $customer->id,
        'amount' => 250,
        'note' => 'Initial top-up',
        'distributed_by' => $this->sp->id,
        'created_at' => now(),
    ]);

    Livewire::test(Wallet::class)
        ->assertCanSeeTableRecords([$distribution])
        ->assertSee('Initial top-up');
});

test('sp can list and view senders in their pool', function () {
    $sender = Sender::factory()->create();
    $sender->users()->attach($this->sp);

    $otherSender = Sender::factory()->create();

    Livewire::test(ListSenders::class)
        ->assertCanSeeTableRecords([$sender])
        ->assertCanNotSeeTableRecords([$otherSender]);

    Livewire::test(ViewSender::class, ['record' => $sender->id])
        ->assertSuccessful()
        ->assertSee($sender->sender_id);
});
