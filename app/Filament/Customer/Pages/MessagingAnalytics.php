<?php

namespace App\Filament\Customer\Pages;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Concerns\HasDateFilter;
use App\Filament\Customer\Widgets\CampaignPerformanceChart;
use App\Filament\Customer\Widgets\CampaignStatsWidget;
use App\Filament\Customer\Widgets\DeliveryRateTrendChart;
use App\Filament\Customer\Widgets\MessageStatsByPeriodWidget;
use App\Filament\Customer\Widgets\MessageTrendChart;
use BackedEnum;
use Filament\Pages\Dashboard as BasePage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MessagingAnalytics extends BasePage
{
    use HasDateFilter;

    protected static string $routePath = 'analytics/messaging';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Analytics;

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Messaging Analytics';

    protected static ?string $navigationLabel = 'Messaging';

    public function getWidgets(): array
    {
        return [
            MessageStatsByPeriodWidget::class,
            CampaignStatsWidget::class,
            MessageTrendChart::class,
            DeliveryRateTrendChart::class,
            CampaignPerformanceChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 3;
    }
}
