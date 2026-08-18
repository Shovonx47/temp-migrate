<?php

namespace App\Filament\Sp\Pages;

use App\Enums\SpNavigationGroup;
use App\Filament\Concerns\HasDateFilter;
use App\Filament\Sp\Widgets\SpCustomerPerformanceTableWidget;
use App\Filament\Sp\Widgets\SpDeliveryStatsWidget;
use App\Filament\Sp\Widgets\SpFinancialOverviewWidget;
use App\Filament\Sp\Widgets\SpMessageTrendChart;
use App\Filament\Sp\Widgets\SpOperatorTrafficChart;
use App\Filament\Sp\Widgets\SpOwnMarginStatsWidget;
use BackedEnum;
use Filament\Pages\Dashboard as BasePage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Reports extends BasePage
{
    use HasDateFilter;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = SpNavigationGroup::Reports;

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $title = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static string $routePath = 'reports';

    public function getWidgets(): array
    {
        return [
            SpOwnMarginStatsWidget::class,
            SpDeliveryStatsWidget::class,
            SpFinancialOverviewWidget::class,
            SpMessageTrendChart::class,
            SpOperatorTrafficChart::class,
            SpCustomerPerformanceTableWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
