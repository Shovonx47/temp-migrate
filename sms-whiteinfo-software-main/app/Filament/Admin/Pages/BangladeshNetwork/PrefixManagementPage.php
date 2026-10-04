<?php

namespace App\Filament\Admin\Pages\BangladeshNetwork;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PrefixManagementPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::BangladeshNetwork;

    protected static ?string $navigationLabel = 'Prefix Management';

    protected static ?string $title = 'Prefix Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'bd-network/prefix';

    protected string $view = 'filament.admin.blank';
}
