<?php

namespace App\Filament\Admin\Pages\BangladeshNetwork;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class DndPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::BangladeshNetwork;

    protected static ?string $navigationLabel = 'DND';

    protected static ?string $title = 'Do Not Disturb (DND)';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'bd-network/dnd';

    protected string $view = 'filament.admin.blank';
}
