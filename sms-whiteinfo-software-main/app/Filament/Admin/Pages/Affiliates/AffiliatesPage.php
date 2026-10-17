<?php

namespace App\Filament\Admin\Pages\Affiliates;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AffiliatesPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Affiliates;

    protected static ?string $navigationLabel = 'Affiliate List';

    protected static ?string $title = 'Affiliates';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'affiliates/list';

    protected string $view = 'filament.admin.blank';
}
