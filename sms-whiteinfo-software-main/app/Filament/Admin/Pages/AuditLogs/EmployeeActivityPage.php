<?php

namespace App\Filament\Admin\Pages\AuditLogs;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class EmployeeActivityPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::AuditLogs;

    protected static ?string $navigationLabel = 'Employee Activity';

    protected static ?string $title = 'Employee Activity';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'audit-logs/employees';

    protected string $view = 'filament.admin.blank';
}
