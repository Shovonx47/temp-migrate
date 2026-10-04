<?php

namespace App\Filament\Admin\Pages\Dlr;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class DeliveryCheckPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Dlr;

    protected static ?string $navigationLabel = 'Delivery Check';

    protected static ?string $title = 'Delivery Check';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'dlr/check';

    protected string $view = 'filament.admin.blank';
}
