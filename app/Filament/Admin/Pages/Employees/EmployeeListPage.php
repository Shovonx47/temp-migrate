<?php

namespace App\Filament\Admin\Pages\Employees;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class EmployeeListPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Employees;

    protected static ?string $navigationLabel = 'Employee List';

    protected static ?string $title = 'Employee List';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'employees/list';

    protected string $view = 'filament.admin.blank';
}
