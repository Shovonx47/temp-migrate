<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\LiveMessageStreamWidget;
use App\Filament\Admin\Widgets\MissionControlDashboardWidget;
use BackedEnum;
use Filament\Pages\Dashboard as BasePage;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BasePage
{
    protected static ?string $title = 'Live Dashboard';

    protected static ?string $navigationLabel = 'Live Dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;


    public function getTitle(): string|Htmlable
    {
        return 'Live Dashboard';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Live Dashboard';
    }

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

