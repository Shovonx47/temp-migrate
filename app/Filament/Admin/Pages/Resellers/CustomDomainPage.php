<?php

namespace App\Filament\Admin\Pages\Resellers;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CustomDomainPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $navigationLabel = 'Custom Domain';

    protected static ?string $title = 'Custom Domain';

    protected static ?int $navigationSort = 16;

    protected static ?string $slug = 'resellers/custom-domain';

    protected string $view = 'filament.admin.blank';
}
