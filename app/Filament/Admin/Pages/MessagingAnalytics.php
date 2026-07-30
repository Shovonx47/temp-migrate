<?php

namespace App\Filament\Admin\Pages;

use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Widgets\DeliveryRateTrendChart;
use App\Filament\Admin\Widgets\DlrSummaryChart;
use App\Filament\Admin\Widgets\FailedReasonWidget;
use App\Filament\Admin\Widgets\MessageStatsByPeriodWidget;
use App\Filament\Admin\Widgets\MessageTrendChart;
use App\Filament\Admin\Widgets\SourceDistributionChart;
use App\Filament\Concerns\HasDateFilter;
use BackedEnum;
use Filament\Pages\Dashboard as BasePage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MessagingAnalytics extends BasePage
{
    use HasDateFilter;

    protected static string $routePath = 'analytics/messaging';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Analytics;

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Messaging Analytics';

    protected static ?string $navigationLabel = 'Messaging';

    public function getWidgets(): array
    {
        return [
            MessageStatsByPeriodWidget::class,
            MessageTrendChart::class,
            DeliveryRateTrendChart::class,
            DlrSummaryChart::class,
            FailedReasonWidget::class,
            SourceDistributionChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 3;
    }
}
