<?php

namespace App\Filament\Admin\Pages\Billing;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class BillingDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Billing;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Billing Dashboard';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.admin.billing.placeholder';
}
