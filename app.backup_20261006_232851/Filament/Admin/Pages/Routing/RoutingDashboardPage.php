<?php

namespace App\Filament\Admin\Pages\Routing;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class RoutingDashboardPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Routing;

    protected static ?string $navigationLabel = 'Routing Dashboard';

    protected static ?string $title = 'Routing Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'routing/dashboard';

    protected string $view = 'filament.admin.blank';
}
