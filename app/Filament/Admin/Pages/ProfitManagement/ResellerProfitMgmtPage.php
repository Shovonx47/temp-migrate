<?php

namespace App\Filament\Admin\Pages\ProfitManagement;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ResellerProfitMgmtPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::ProfitManagement;

    protected static ?string $navigationLabel = 'Reseller Profit';

    protected static ?string $title = 'Reseller Profit';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'profit/reseller';

    protected string $view = 'filament.admin.blank';
}
