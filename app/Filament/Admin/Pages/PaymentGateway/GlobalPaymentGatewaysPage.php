<?php

namespace App\Filament\Admin\Pages\PaymentGateway;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class GlobalPaymentGatewaysPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::PaymentGateway;

    protected static ?string $navigationLabel = 'Global Gateways';

    protected static ?string $title = 'Global Payment Gateways';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'payment-gateways/global';

    protected string $view = 'filament.admin.blank';
}
