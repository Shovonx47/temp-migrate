<?php

namespace App\Filament\Admin\Pages\Campaigns;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CampaignReportsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Campaigns;

    protected static ?string $navigationLabel = 'Campaign Reports';

    protected static ?string $title = 'Campaign Reports';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'campaigns/reports';

    protected string $view = 'filament.admin.blank';
}
