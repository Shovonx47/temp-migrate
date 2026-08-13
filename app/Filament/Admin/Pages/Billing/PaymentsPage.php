<?php

namespace App\Filament\Admin\Pages\Billing;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PaymentsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Billing;

    protected static ?string $navigationLabel = 'Payments';

    protected static ?string $title = 'Payments';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.admin.billing.placeholder';
}
