<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class FinancialReportsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Reports;

    protected static ?string $navigationLabel = 'Financial Reports';

    protected static ?string $title = 'Financial Reports';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'reports/financial';

    protected string $view = 'filament.admin.blank';
}
