<?php

namespace App\Filament\Admin\Pages\RolesPermissions;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class RolesPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::RolesPermissions;

    protected static ?string $navigationLabel = 'Roles';

    protected static ?string $title = 'Roles';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'roles-permissions/roles';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.admin.blank';
}
