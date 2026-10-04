<?php

namespace App\Filament\Admin\Pages\BangladeshNetwork;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MnpPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::BangladeshNetwork;

    protected static ?string $navigationLabel = 'MNP';

    protected static ?string $title = 'Mobile Number Portability';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'bd-network/mnp';

    protected string $view = 'filament.admin.blank';
}
