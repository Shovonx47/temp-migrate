<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class NonMaskingReportPage extends AbstractReportPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Reports;

    protected static ?string $navigationLabel = 'Non-Masking Report';

    protected static ?string $title = 'Non-Masking Report';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'reports/non-masking';

    protected function reportType(): string
    {
        return 'non_masking';
    }
}
