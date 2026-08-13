<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MaskingReportPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Reports;

    protected static ?string $navigationLabel = 'Masking Report';

    protected static ?string $title = 'Masking Report';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'reports/masking';

    protected string $view = 'filament.admin.blank';
}
