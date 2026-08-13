<?php

namespace App\Filament\Admin\Pages\Resellers;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ResellerBillingPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $navigationLabel = 'Reseller Billing';

    protected static ?string $title = 'Reseller Billing';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'resellers/billing';

    protected string $view = 'filament.admin.blank';
}
