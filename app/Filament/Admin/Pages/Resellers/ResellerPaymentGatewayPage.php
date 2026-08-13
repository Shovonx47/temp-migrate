<?php

namespace App\Filament\Admin\Pages\Resellers;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ResellerPaymentGatewayPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $navigationLabel = 'Payment Gateway';

    protected static ?string $title = 'Reseller Payment Gateway';

    protected static ?int $navigationSort = 17;

    protected static ?string $slug = 'resellers/payment-gateway';

    protected string $view = 'filament.admin.blank';
}
