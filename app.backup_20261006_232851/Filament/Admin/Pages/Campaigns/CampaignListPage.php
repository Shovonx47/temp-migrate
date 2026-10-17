<?php

namespace App\Filament\Admin\Pages\Campaigns;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CampaignListPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Campaigns;

    protected static ?string $navigationLabel = 'Campaign List';

    protected static ?string $title = 'Campaigns';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'campaigns/list';

    protected string $view = 'filament.admin.blank';
}
