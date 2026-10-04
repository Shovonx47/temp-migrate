<?php

namespace App\Filament\Admin\Pages\Affiliates;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AffiliateDashboardPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Affiliates;

    protected static ?string $navigationLabel = 'Affiliate Dashboard';

    protected static ?string $title = 'Affiliate Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'affiliates/dashboard';

    protected string $view = 'filament.admin.blank';
}
