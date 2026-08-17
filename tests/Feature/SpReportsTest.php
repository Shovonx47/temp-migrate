<?php

use App\Enums\DepositStatus;
use App\Enums\MessageStatus;
use App\Enums\UserType;
use App\Filament\Admin\Pages\Resellers\ResellerReportsPage;
use App\Filament\Admin\Widgets\SpAdminFinancialOverviewWidget;
use App\Filament\Admin\Widgets\SpAdminTrafficTrendChart;
use App\Filament\Admin\Widgets\SpMarginBreakdownWidget;
use App\Filament\Admin\Widgets\SpMarginStatsWidget;
use App\Filament\Admin\Widgets\SpResellerPerformanceTableWidget;
use App\Filament\Sp\Pages\Reports;
use App\Filament\Sp\Widgets\SpCustomerPerformanceTableWidget;
use App\Filament\Sp\Widgets\SpDeliveryStatsWidget;
use App\Filament\Sp\Widgets\SpFinancialOverviewWidget;
use App\Filament\Sp\Widgets\SpMessageTrendChart;
use App\Filament\Sp\Widgets\SpOperatorTrafficChart;
use App\Filament\Sp\Widgets\SpOwnMarginStatsWidget;
use App\Models\Deposit;
use App\Models\Message;
use App\Models\SpDistribution;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('sp can view reports dashboard on sp panel', function () {
    Filament::setCurrentPanel(Filament::getPanel('sp'));

    $sp = User::factory()->sp()->create(['phone' => '+8801711111111', 'balance' => 5000]);
    $customer = User::factory()->create([
        'type' => UserType::Customer,
        'phone' => '+8801722222222',
        'sp_id' => $sp->id,
        'balance' => 100,
    ]);

    actingAs($sp, 'sp');

    Livewire::test(Reports::class)
        ->assertSuccessful();
});

test('sp reports widgets render successfully with customer data', function () {
    Filament::setCurrentPanel(Filament::getPanel('sp'));

    $sp = User::factory()->sp()->create(['phone' => '+8801711111111', 'balance' => 5000]);
    $customer = User::factory()->create([
        'type' => UserType::Customer,
        'phone' => '+8801722222222',
        'sp_id' => $sp->id,
        'balance' => 100,
    ]);

    DB::table('messages')->insert([
        'user_id' => $customer->id,
        'to' => '+8801712345678',
        'body' => 'Test message',
        'status' => 'delivered',
        'cost' => 0.50,
        'rate' => 0.50,
        'parts' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    SpDistribution::query()->create([
        'sp_id' => $sp->id,
        'customer_id' => $customer->id,
        'amount' => 500,
        'distributed_by' => $sp->id,
        'created_at' => now(),
    ]);

    Deposit::factory()->create([
        'user_id' => $customer->id,
        'amount' => 50,
        'status' => DepositStatus::Completed,
        'created_at' => now(),
    ]);

    actingAs($sp, 'sp');

    Livewire::test(SpOwnMarginStatsWidget::class)
        ->assertSuccessful();

    Livewire::test(SpDeliveryStatsWidget::class)
        ->assertSuccessful();

    Livewire::test(SpFinancialOverviewWidget::class)
        ->assertSuccessful();

    Livewire::test(SpMessageTrendChart::class)
        ->assertSuccessful();

    Livewire::test(SpOperatorTrafficChart::class)
        ->assertSuccessful();

    Livewire::test(SpCustomerPerformanceTableWidget::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$customer]);
});

test('admin can view reseller reports page on superman panel', function () {
    Filament::setCurrentPanel(Filament::getPanel('superman'));

    $admin = User::factory()->superman()->create(['phone' => '+8801700000000']);
    $sp = User::factory()->sp()->create(['name' => 'Apex Reseller', 'phone' => '+8801711111111']);

    actingAs($admin, 'superman');

    Livewire::test(ResellerReportsPage::class)
        ->assertSuccessful();
});

test('superman reseller reports widgets render successfully', function () {
    Filament::setCurrentPanel(Filament::getPanel('superman'));

    $admin = User::factory()->superman()->create(['phone' => '+8801700000000']);
    $sp = User::factory()->sp()->create(['name' => 'Alpha S P', 'phone' => '+8801711111111', 'balance' => 10000]);
    $customer = User::factory()->create([
        'type' => UserType::Customer,
        'phone' => '+8801722222222',
        'sp_id' => $sp->id,
        'balance' => 200,
    ]);

    DB::table('messages')->insert([
        'user_id' => $customer->id,
        'to' => '+8801812345678',
        'body' => 'Test message',
        'status' => 'delivered',
        'cost' => 0.60,
        'rate' => 0.60,
        'parts' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    actingAs($admin, 'superman');

    Livewire::test(SpMarginStatsWidget::class)
        ->assertSuccessful();

    Livewire::test(SpAdminFinancialOverviewWidget::class)
        ->assertSuccessful();

    Livewire::test(SpMarginBreakdownWidget::class)
        ->assertSuccessful();

    Livewire::test(SpAdminTrafficTrendChart::class)
        ->assertSuccessful();

    Livewire::test(SpResellerPerformanceTableWidget::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$sp]);
});

test('superman can filter reseller reports by specific sp', function () {
    Filament::setCurrentPanel(Filament::getPanel('superman'));

    $admin = User::factory()->superman()->create(['phone' => '+8801700000000']);
    $sp1 = User::factory()->sp()->create(['name' => 'First S P', 'phone' => '+8801711111111']);
    $sp2 = User::factory()->sp()->create(['name' => 'Second S P', 'phone' => '+8801722222222']);

    actingAs($admin, 'superman');

    Livewire::test(SpResellerPerformanceTableWidget::class, [
        'pageFilters' => [
            'sp_id' => $sp1->id,
            'date_range' => 'last_30_days',
        ],
    ])
        ->assertCanSeeTableRecords([$sp1])
        ->assertCanNotSeeTableRecords([$sp2]);
});


