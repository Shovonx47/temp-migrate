<?php

namespace App\Filament\Admin\Pages\SystemSettings;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class NotificationSettingsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SystemSettings;

    protected static ?string $navigationLabel = 'Notification Settings';

    protected static ?string $title = 'Notification Settings';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'system-settings/notifications';

    protected string $view = 'filament.admin.blank';
}
