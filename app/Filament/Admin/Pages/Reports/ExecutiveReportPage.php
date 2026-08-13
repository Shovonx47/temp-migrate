<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ExecutiveReportPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Reports;

    protected static ?string $navigationLabel = 'Executive Report';

    protected static ?string $title = 'Executive Report';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'reports/executive';

    protected string $view = 'filament.admin.blank';
}
