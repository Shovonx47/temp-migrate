<?php

namespace App\Filament\Admin\Pages\ProfitManagement;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CustomerProfitPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::ProfitManagement;

    protected static ?string $navigationLabel = 'Customer Profit';

    protected static ?string $title = 'Customer Profit';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'profit/customer';

    protected string $view = 'filament.admin.blank';
}
