<?php

namespace App\Filament\Admin\Pages\Affiliates;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AffiliateReportsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Affiliates;

    protected static ?string $navigationLabel = 'Affiliate Reports';

    protected static ?string $title = 'Affiliate Reports';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'affiliates/reports';

    protected string $view = 'filament.admin.blank';
}
