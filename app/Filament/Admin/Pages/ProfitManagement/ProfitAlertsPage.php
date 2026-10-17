<?php

namespace App\Filament\Admin\Pages\ProfitManagement;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ProfitAlertsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::ProfitManagement;

    protected static ?string $navigationLabel = 'Profit Alerts';

    protected static ?string $title = 'Profit Alerts';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'profit/alerts';

    protected string $view = 'filament.admin.blank';
}
