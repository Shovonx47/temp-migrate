<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MaskingReportPage extends AbstractReportPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Reports;

    protected static ?string $navigationLabel = 'Masking Report';

    protected static ?string $title = 'Masking Report';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'reports/masking';

    protected function reportType(): string
    {
        return 'masking';
    }
}
