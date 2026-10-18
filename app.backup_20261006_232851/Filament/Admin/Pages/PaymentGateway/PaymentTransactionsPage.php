<?php

namespace App\Filament\Admin\Pages\PaymentGateway;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PaymentTransactionsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|\UnitEnum|null $navigationGroup = 'PaymentGateway';

    protected static ?string $navigationLabel = 'Payment Transactions';

    protected static ?string $title = 'Payment Transactions';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'payment-gateways/transactions';

    protected string $view = 'filament.admin.blank';
}
