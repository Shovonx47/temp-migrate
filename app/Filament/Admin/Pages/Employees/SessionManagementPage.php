<?php

namespace App\Filament\Admin\Pages\Employees;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SessionManagementPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Employees;

    protected static ?string $navigationLabel = 'Session Management';

    protected static ?string $title = 'Session Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'employees/sessions';

    protected string $view = 'filament.admin.blank';
}
