<?php

namespace App\Filament\Admin\Pages\BangladeshNetwork;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MnoManagementPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::BangladeshNetwork;

    protected static ?string $navigationLabel = 'MNO Management';

    protected static ?string $title = 'MNO Management';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'bd-network/mno';

    protected string $view = 'filament.admin.blank';
}
