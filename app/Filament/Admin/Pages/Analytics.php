<?php

namespace App\Filament\Admin\Pages;

use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Widgets\GatewayCostBreakdownWidget;
use App\Filament\Admin\Widgets\GatewayPerformanceChart;
use App\Filament\Admin\Widgets\OperatorTrafficChart;
use App\Filament\Admin\Widgets\ProfitStatsWidget;
use App\Filament\Admin\Widgets\ProfitTrendChart;
use BackedEnum;
use Filament\Pages\Dashboard as BasePage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Analytics extends BasePage
{
    protected static string $routePath = 'analytics';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Analytics;

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Analytics';

    protected static ?string $navigationLabel = 'Cost & Profit';

    public function getWidgets(): array
    {
        return [
            ProfitStatsWidget::class,
            ProfitTrendChart::class,
            GatewayCostBreakdownWidget::class,
            GatewayPerformanceChart::class,
            OperatorTrafficChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 3;
    }
}
