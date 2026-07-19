<?php

namespace App\Filament\Customer\Pages;

use App\Filament\Customer\Resources\Billing\Deposits\Actions\DepositAction;
use App\Filament\Customer\Widgets\BalanceWidget;
use App\Filament\Customer\Widgets\CampaignPerformanceChart;
use App\Filament\Customer\Widgets\CampaignStatsWidget;
use App\Filament\Customer\Widgets\ContactStatsWidget;
use App\Filament\Customer\Widgets\MessageTrendChart;
use App\Filament\Customer\Widgets\MessageUsageWidget;
use App\Filament\Customer\Widgets\RecentTransactionsWidget;
use App\Filament\Customer\Widgets\SpendingTrendChart;
use Filament\Pages\Dashboard as BasePage;

class Dashboard extends BasePage
{
    protected function getHeaderActions(): array
    {
        return [
            DepositAction::make(),
        ];
    }

    public function getWidgets(): array
    {
        return [
            BalanceWidget::class,
            CampaignStatsWidget::class,
            MessageUsageWidget::class,
            ContactStatsWidget::class,
            MessageTrendChart::class,
            SpendingTrendChart::class,
            CampaignPerformanceChart::class,
            RecentTransactionsWidget::class,
        ];
    }
}
