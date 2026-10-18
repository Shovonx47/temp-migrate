<?php

namespace App\Filament\Admin\Pages\Monitoring;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class TpsMonitorPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Monitoring;

    protected static ?string $navigationLabel = 'TPS Monitor';

    protected static ?string $title = 'TPS Monitor';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'monitoring/tps';

    protected string $view = 'filament.admin.blank';
}
