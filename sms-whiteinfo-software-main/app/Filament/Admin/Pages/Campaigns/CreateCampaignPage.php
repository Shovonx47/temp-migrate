<?php

namespace App\Filament\Admin\Pages\Campaigns;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CreateCampaignPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlusCircle;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Campaigns;

    protected static ?string $navigationLabel = 'Create Campaign';

    protected static ?string $title = 'Create Campaign';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'campaigns/create';

    protected string $view = 'filament.admin.blank';
}
