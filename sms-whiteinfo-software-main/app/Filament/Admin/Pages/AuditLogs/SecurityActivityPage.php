<?php

namespace App\Filament\Admin\Pages\AuditLogs;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SecurityActivityPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::AuditLogs;

    protected static ?string $navigationLabel = 'Security Activity';

    protected static ?string $title = 'Security Activity';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'audit-logs/security';

    protected string $view = 'filament.admin.blank';
}
