<?php

namespace App\Filament\Admin\Pages\Alerts;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AlertDashboardPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Alerts;

    protected static ?string $navigationLabel = 'Alert Dashboard';

    protected static ?string $title = 'Alert Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'alerts/dashboard';

    protected string $view = 'filament.admin.blank';
}
