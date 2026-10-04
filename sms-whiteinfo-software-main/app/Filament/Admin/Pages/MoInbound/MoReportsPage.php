<?php

namespace App\Filament\Admin\Pages\MoInbound;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MoReportsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::MoInbound;

    protected static ?string $navigationLabel = 'MO Reports';

    protected static ?string $title = 'MO Reports';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'mo/reports';

    protected string $view = 'filament.admin.blank';
}
