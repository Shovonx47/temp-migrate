<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CustomerReportsPage extends AbstractReportPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Reports;

    protected static ?string $navigationLabel = 'Customer Reports';

    protected static ?string $title = 'Customer Reports';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'reports/customer';

    protected function reportType(): string
    {
        return 'customer';
    }
}
