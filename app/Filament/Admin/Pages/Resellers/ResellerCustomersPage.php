<?php

namespace App\Filament\Admin\Pages\Resellers;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ResellerCustomersPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $navigationLabel = 'Reseller Customers';

    protected static ?string $title = 'Reseller Customers';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'resellers/customers';

    protected string $view = 'filament.admin.blank';
}
