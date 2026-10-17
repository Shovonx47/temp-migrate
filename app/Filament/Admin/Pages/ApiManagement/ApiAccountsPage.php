<?php

namespace App\Filament\Admin\Pages\ApiManagement;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ApiAccountsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::ApiManagement;

    protected static ?string $navigationLabel = 'API Accounts';

    protected static ?string $title = 'API Accounts';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'api-management/accounts';

    protected string $view = 'filament.admin.blank';
}
