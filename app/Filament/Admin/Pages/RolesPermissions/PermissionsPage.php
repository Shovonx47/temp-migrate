<?php

namespace App\Filament\Admin\Pages\RolesPermissions;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PermissionsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::RolesPermissions;

    protected static ?string $navigationLabel = 'Permissions';

    protected static ?string $title = 'Permissions';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'roles-permissions/permissions';

    protected string $view = 'filament.admin.blank';
}
