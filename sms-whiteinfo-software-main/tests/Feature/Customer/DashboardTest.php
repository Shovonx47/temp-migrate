<?php

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Filament\Customer\Pages\BillingAnalytics;
use App\Filament\Customer\Pages\Dashboard;
use App\Filament\Customer\Pages\MessagingAnalytics;
use App\Filament\Customer\Widgets\BalanceWidget;
use App\Filament\Customer\Widgets\BillingStatsWidget;
use App\Filament\Customer\Widgets\CampaignPerformanceChart;
use App\Filament\Customer\Widgets\CampaignStatsWidget;
use App\Filament\Customer\Widgets\ContactStatsWidget;
use App\Filament\Customer\Widgets\MessageStatsByPeriodWidget;
use App\Filament\Customer\Widgets\MessageTrendChart;
use App\Filament\Customer\Widgets\MessageUsageWidget;
use App\Filament\Customer\Widgets\RecentTransactionsWidget;
use App\Filament\Customer\Widgets\SpendingTrendChart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'type' => UserType::Customer,
        'status' => UserStatus::Approved,
        'front_nid' => 'nid/front.jpg',
        'back_nid' => 'nid/back.jpg',
    ]);
});

test('dashboard is minimal with realtime health widgets only', function () {
    actingAs($this->user, 'customer');

    $widgets = (new Dashboard)->getWidgets();

    expect($widgets)->toContain(
        BalanceWidget::class,
        MessageUsageWidget::class,
        ContactStatsWidget::class,
        RecentTransactionsWidget::class,
    );

    expect($widgets)->not->toContain(
        CampaignStatsWidget::class,
        MessageTrendChart::class,
        SpendingTrendChart::class,
        CampaignPerformanceChart::class,
    );
});

test('dashboard loads successfully', function () {
    actingAs($this->user, 'customer');

    $this->get('/')->assertOk();
});

test('messaging analytics page renders period widgets and date filter', function () {
    actingAs($this->user, 'customer');

    expect((new MessagingAnalytics)->getWidgets())->toContain(
        MessageStatsByPeriodWidget::class,
        CampaignStatsWidget::class,
        MessageTrendChart::class,
    );

    $this->get('/analytics/messaging')
        ->assertOk()
        ->assertSee('date_range')
        ->assertSee('Last 30 Days');
});

test('billing analytics page renders billing widgets and date filter', function () {
    actingAs($this->user, 'customer');

    expect((new BillingAnalytics)->getWidgets())->toContain(
        BillingStatsWidget::class,
        SpendingTrendChart::class,
    );

    $this->get('/analytics/billing')
        ->assertOk()
        ->assertSee('date_range')
        ->assertSee('Last 30 Days');
});
