<?php

namespace App\Filament\Admin\Pages\Resellers;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ResellersPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $navigationLabel = 'Reseller List';

    protected static ?string $title = 'Resellers';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'resellers/list';

    protected string $view = 'filament.admin.blank';
}
