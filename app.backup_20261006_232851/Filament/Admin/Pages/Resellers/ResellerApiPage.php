<?php

namespace App\Filament\Admin\Pages\Resellers;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ResellerApiPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCodeBracket;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $navigationLabel = 'Reseller API';

    protected static ?string $title = 'Reseller API';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'resellers/api';

    protected string $view = 'filament.admin.blank';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
