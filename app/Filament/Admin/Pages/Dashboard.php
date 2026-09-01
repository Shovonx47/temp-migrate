<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\LiveMessageStreamWidget;
use App\Filament\Admin\Widgets\MissionControlDashboardWidget;
use Filament\Pages\Dashboard as BasePage;

class Dashboard extends BasePage
{
    protected static ?string $title = 'Dashboard';

    public function getWidgets(): array
    {
        return [
            MissionControlDashboardWidget::class,
            LiveMessageStreamWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
