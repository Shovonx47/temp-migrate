<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\GatewayPerformanceChart;
use App\Filament\Admin\Widgets\MessageStatsWidget;
use App\Filament\Admin\Widgets\MessageTrendChart;
use App\Filament\Admin\Widgets\PendingApprovalsWidget;
use App\Filament\Admin\Widgets\RecentActivityWidget;
use App\Filament\Admin\Widgets\RevenueStatsWidget;
use App\Filament\Admin\Widgets\RevenueTrendChart;
use App\Filament\Admin\Widgets\SystemStatsWidget;
use App\Filament\Admin\Widgets\TopCustomersWidget;
use App\Filament\Admin\Widgets\UserGrowthChart;
use App\Filament\Admin\Widgets\UserStatsWidget;
use Filament\Pages\Dashboard as BasePage;

class Dashboard extends BasePage
{
    protected static ?string $title = 'Dashboard';

    public function getWidgets(): array
    {
        return [
            SystemStatsWidget::class,
            RevenueStatsWidget::class,
            MessageStatsWidget::class,
            UserStatsWidget::class,
            MessageTrendChart::class,
            RevenueTrendChart::class,
            UserGrowthChart::class,
            GatewayPerformanceChart::class,
            RecentActivityWidget::class,
            TopCustomersWidget::class,
            PendingApprovalsWidget::class,
        ];
    }
}
