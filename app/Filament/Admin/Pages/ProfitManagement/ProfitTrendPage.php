<?php

namespace App\Filament\Admin\Pages\ProfitManagement;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ProfitTrendPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::ProfitManagement;

    protected static ?string $navigationLabel = 'Profit Trend';

    protected static ?string $title = 'Profit Trend';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'profit/trend';

    protected string $view = 'filament.admin.blank';
}
