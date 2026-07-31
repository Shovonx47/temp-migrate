<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\MessageStatsWidget;
use App\Filament\Admin\Widgets\PendingApprovalsWidget;
use App\Filament\Admin\Widgets\RecentActivityWidget;
use App\Filament\Admin\Widgets\SystemStatsWidget;
use Filament\Pages\Dashboard as BasePage;

class Dashboard extends BasePage
{
    protected static ?string $title = 'Dashboard';

    public function getWidgets(): array
    {
        return [
            SystemStatsWidget::class,
            MessageStatsWidget::class,
            PendingApprovalsWidget::class,
            RecentActivityWidget::class,
        ];
    }
}
