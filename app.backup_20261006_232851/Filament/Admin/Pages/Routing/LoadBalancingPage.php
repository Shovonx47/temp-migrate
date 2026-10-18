<?php

namespace App\Filament\Admin\Pages\Routing;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class LoadBalancingPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Routing;

    protected static ?string $navigationLabel = 'Load Balancing';

    protected static ?string $title = 'Load Balancing';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'routing/load-balancing';

    protected string $view = 'filament.admin.blank';
}
