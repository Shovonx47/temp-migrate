<?php

namespace App\Filament\Admin\Pages\Monitoring;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class LiveMonitoringPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Monitoring;

    protected static ?string $navigationLabel = 'Live Monitoring';

    protected static ?string $title = 'Live Monitoring';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'monitoring/live';

    protected string $view = 'filament.admin.blank';
}
