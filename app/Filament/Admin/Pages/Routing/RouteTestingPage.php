<?php

namespace App\Filament\Admin\Pages\Routing;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class RouteTestingPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Routing;

    protected static ?string $navigationLabel = 'Route Testing';

    protected static ?string $title = 'Route Testing';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'routing/testing';

    protected string $view = 'filament.admin.blank';
}
