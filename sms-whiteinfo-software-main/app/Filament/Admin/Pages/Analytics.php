<?php

namespace App\Filament\Admin\Pages;

use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Widgets\GatewayCostBreakdownWidget;
use App\Filament\Admin\Widgets\GatewayPerformanceChart;
use App\Filament\Admin\Widgets\OperatorCostBreakdownWidget;
use App\Filament\Admin\Widgets\OperatorTrafficChart;
use App\Filament\Admin\Widgets\ProfitStatsWidget;
use App\Filament\Admin\Widgets\ProfitTrendChart;
use App\Filament\Admin\Widgets\TopOperatorsWidget;
use App\Filament\Concerns\HasDateFilter;
use BackedEnum;
use Filament\Pages\Dashboard as BasePage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Analytics extends BasePage
{
    use HasDateFilter;

    protected static string $routePath = 'analytics/cost-profit';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Analytics;

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Cost & Profit Analytics';

    protected static ?string $navigationLabel = 'Cost & Profit';

    public function getWidgets(): array
    {
        return [
            ProfitStatsWidget::class,
            ProfitTrendChart::class,
            GatewayCostBreakdownWidget::class,
            OperatorCostBreakdownWidget::class,
            GatewayPerformanceChart::class,
            OperatorTrafficChart::class,
            TopOperatorsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 3;
    }
}
