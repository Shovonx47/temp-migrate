<?php

namespace App\Filament\Admin\Pages\SystemSettings;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MaintenanceModePage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SystemSettings;

    protected static ?string $navigationLabel = 'Maintenance Mode';

    protected static ?string $title = 'Maintenance Mode';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'system-settings/maintenance';

    protected string $view = 'filament.admin.blank';
}
