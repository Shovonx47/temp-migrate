<?php

namespace App\Filament\Admin\Pages\Resellers;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ResellerSenderPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $navigationLabel = 'Reseller Sender';

    protected static ?string $title = 'Reseller Sender';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'resellers/sender';

    protected string $view = 'filament.admin.blank';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
