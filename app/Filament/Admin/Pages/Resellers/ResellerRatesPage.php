<?php

namespace App\Filament\Admin\Pages\Resellers;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ResellerRatesPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $navigationLabel = 'Reseller Rates';

    protected static ?string $title = 'Reseller Rates';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'resellers/rates';

    protected string $view = 'filament.admin.blank';
}
