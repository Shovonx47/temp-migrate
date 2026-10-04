<?php

namespace App\Filament\Customer\Pages;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Customer\Widgets\CustomerLiveMessageStreamWidget;
use App\Filament\Customer\Widgets\CustomerLiveQueueStatsWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class LiveQueuePage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Messaging;

    protected static ?string $navigationLabel = 'Live Queue';

    protected static ?string $title = 'My Live Queue';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'messaging/live-queue';

    protected string $view = 'filament.customer.pages.live-queue';

    public function getWidgets(): array
    {
        return [
            CustomerLiveQueueStatsWidget::class,
            CustomerLiveMessageStreamWidget::class,
        ];
    }
}
