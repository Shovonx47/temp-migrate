<?php

namespace App\Filament\Admin\Pages\Monitoring;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ProviderHealthMonitorPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServer;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Monitoring;

    protected static ?string $navigationLabel = 'Provider Health';

    protected static ?string $title = 'Provider Health Monitor';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'monitoring/provider-health';

    protected string $view = 'filament.admin.blank';
}
