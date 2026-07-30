<?php

namespace App\Filament\Admin\Pages;

use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Widgets\DepositMethodChart;
use App\Filament\Admin\Widgets\DepositTrendChart;
use App\Filament\Admin\Widgets\FinancialStatsWidget;
use App\Filament\Admin\Widgets\SpendingTrendChart;
use App\Filament\Admin\Widgets\TopCustomersByPeriodWidget;
use App\Filament\Admin\Widgets\TransactionVolumeChart;
use App\Filament\Concerns\HasDateFilter;
use BackedEnum;
use Filament\Pages\Dashboard as BasePage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class FinancialAnalytics extends BasePage
{
    use HasDateFilter;

    protected static string $routePath = 'analytics/financial';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Analytics;

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Financial Analytics';

    protected static ?string $navigationLabel = 'Financial';

    public function getWidgets(): array
    {
        return [
            FinancialStatsWidget::class,
            DepositTrendChart::class,
            SpendingTrendChart::class,
            TopCustomersByPeriodWidget::class,
            DepositMethodChart::class,
            TransactionVolumeChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 3;
    }
}
