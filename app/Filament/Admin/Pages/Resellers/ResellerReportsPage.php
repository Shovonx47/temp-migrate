<?php

namespace App\Filament\Admin\Pages\Resellers;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ResellerReportsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $navigationLabel = 'Reseller Reports';

    protected static ?string $title = 'Reseller Reports';

    protected static ?int $navigationSort = 12;

    protected static ?string $slug = 'resellers/reports';

    protected string $view = 'filament.admin.blank';
}
