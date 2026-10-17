<?php

namespace App\Filament\Admin\Pages\Routing;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class RouteHealthPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Routing;

    protected static ?string $navigationLabel = 'Route Health';

    protected static ?string $title = 'Route Health';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'routing/health';

    protected string $view = 'filament.admin.blank';
}
