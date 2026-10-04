<?php

namespace App\Filament\Customer\Pages;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Concerns\HasDateFilter;
use App\Filament\Customer\Resources\Billing\Deposits\Actions\DepositAction;
use App\Filament\Customer\Widgets\BillingStatsWidget;
use App\Filament\Customer\Widgets\DepositMethodChart;
use App\Filament\Customer\Widgets\DepositTrendChart;
use App\Filament\Customer\Widgets\SpendingTrendChart;
use App\Filament\Customer\Widgets\TransactionVolumeChart;
use BackedEnum;
use Filament\Pages\Dashboard as BasePage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class BillingAnalytics extends BasePage
{
    use HasDateFilter;

    protected static string $routePath = 'analytics/billing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Analytics;

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Billing Analytics';

    protected static ?string $navigationLabel = 'Billing';

    protected function getHeaderActions(): array
    {
        return [
            DepositAction::make(),
        ];
    }

    public function getWidgets(): array
    {
        return [
            BillingStatsWidget::class,
            DepositTrendChart::class,
            SpendingTrendChart::class,
            DepositMethodChart::class,
            TransactionVolumeChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
