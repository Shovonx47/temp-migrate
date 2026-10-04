<?php

namespace App\Filament\Admin\Pages\ApiManagement;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ApiSecurityPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::ApiManagement;

    protected static ?string $navigationLabel = 'API Security';

    protected static ?string $title = 'API Security';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'api-management/security';

    protected string $view = 'filament.admin.blank';
}
