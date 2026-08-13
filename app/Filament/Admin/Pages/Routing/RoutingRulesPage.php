<?php

namespace App\Filament\Admin\Pages\Routing;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class RoutingRulesPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Routing;

    protected static ?string $navigationLabel = 'Routing Rules';

    protected static ?string $title = 'Routing Rules';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'routing/rules';

    protected string $view = 'filament.admin.blank';
}
