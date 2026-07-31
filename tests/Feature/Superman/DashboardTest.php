<?php

use App\Enums\UserType;
use App\Filament\Admin\Pages\Analytics;
use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Pages\FinancialAnalytics;
use App\Filament\Admin\Widgets\MessageStatsWidget;
use App\Filament\Admin\Widgets\PendingApprovalsWidget;
use App\Filament\Admin\Widgets\ProfitStatsWidget;
use App\Filament\Admin\Widgets\RecentActivityWidget;
use App\Filament\Admin\Widgets\RevenueStatsWidget;
use App\Filament\Admin\Widgets\SystemStatsWidget;
use App\Filament\Admin\Widgets\TopCustomersWidget;
use App\Filament\Admin\Widgets\TopOperatorsWidget;
use App\Filament\Admin\Widgets\UserStatsWidget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['type' => UserType::Superman]);
});

test('dashboard is minimal with only realtime health widgets', function () {
    actingAs($this->user, 'superman');

    $widgets = (new Dashboard)->getWidgets();

    expect($widgets)->toContain(
        SystemStatsWidget::class,
        MessageStatsWidget::class,
        PendingApprovalsWidget::class,
        RecentActivityWidget::class,
    );

    expect($widgets)->not->toContain(
        RevenueStatsWidget::class,
        UserStatsWidget::class,
        ProfitStatsWidget::class,
        TopCustomersWidget::class,
        TopOperatorsWidget::class,
    );
});

test('dashboard loads successfully', function () {
    actingAs($this->user, 'superman');

    $this->get('/superman')->assertOk();
});

test('financial analytics page renders moved revenue and user widgets', function () {
    actingAs($this->user, 'superman');

    $widgets = (new FinancialAnalytics)->getWidgets();

    expect($widgets)->toContain(
        RevenueStatsWidget::class,
        UserStatsWidget::class,
    );

    expect($widgets)->not->toContain(TopCustomersWidget::class);
});

test('financial analytics page loads successfully', function () {
    actingAs($this->user, 'superman');

    $this->get('/superman/analytics/financial')
        ->assertOk()
        ->assertSee('date_range')
        ->assertSee('Last 30 Days');
});

test('cost and profit analytics page renders top operators widget', function () {
    actingAs($this->user, 'superman');

    $widgets = (new Analytics)->getWidgets();

    expect($widgets)->toContain(
        ProfitStatsWidget::class,
        TopOperatorsWidget::class,
    );
});

test('cost and profit analytics page loads successfully', function () {
    actingAs($this->user, 'superman');

    $this->get('/superman/analytics/cost-profit')
        ->assertOk()
        ->assertSee('date_range')
        ->assertSee('Last 30 Days');
});
