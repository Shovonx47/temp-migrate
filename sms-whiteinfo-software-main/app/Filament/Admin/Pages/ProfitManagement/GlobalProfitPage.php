<?php

namespace App\Filament\Admin\Pages\ProfitManagement;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class GlobalProfitPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::ProfitManagement;

    protected static ?string $navigationLabel = 'Global Profit';

    protected static ?string $title = 'Global Profit';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'profit/global';

    protected string $view = 'filament.admin.blank';
}
