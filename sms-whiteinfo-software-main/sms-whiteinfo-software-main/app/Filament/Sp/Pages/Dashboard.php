<?php

namespace App\Filament\Sp\Pages;

use App\Filament\Sp\Widgets\SpBalanceWidget;
use App\Filament\Sp\Widgets\SpStatsWidget;
use BackedEnum;
use Filament\Pages\Dashboard as BasePage;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BasePage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'S P Dashboard';

    public function getWidgets(): array
    {
        return [
            SpBalanceWidget::class,
            SpStatsWidget::class,
        ];
    }
}
