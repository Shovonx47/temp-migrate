<?php

namespace App\Filament\Admin\Pages\SystemSettings;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class DlrSettingsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SystemSettings;

    protected static ?string $navigationLabel = 'DLR Settings';

    protected static ?string $title = 'DLR Settings';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'system-settings/dlr';

    protected string $view = 'filament.admin.blank';
}
