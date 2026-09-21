<?php

namespace App\Filament\Admin\Pages\Alerts;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AlertConfigurationPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Alerts;

    protected static ?string $navigationLabel = 'Alert Configuration';

    protected static ?string $title = 'Alert Configuration';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'alerts/configuration';

    protected string $view = 'filament.admin.blank';
}
