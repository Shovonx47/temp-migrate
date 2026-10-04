<?php

namespace App\Filament\Admin\Pages\MoInbound;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MoRoutingPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::MoInbound;

    protected static ?string $navigationLabel = 'MO Routing';

    protected static ?string $title = 'MO Routing';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'mo/routing';

    protected string $view = 'filament.admin.blank';
}
