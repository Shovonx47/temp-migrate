<?php

namespace App\Filament\Admin\Pages\Resellers;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class WhiteLabelPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $navigationLabel = 'White Label';

    protected static ?string $title = 'White Label';

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'resellers/white-label';

    protected string $view = 'filament.admin.blank';
}
