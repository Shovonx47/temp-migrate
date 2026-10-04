<?php

namespace App\Filament\Admin\Pages\PaymentGateway;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PaymentReportsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::PaymentGateway;

    protected static ?string $navigationLabel = 'Payment Reports';

    protected static ?string $title = 'Payment Reports';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'payment-gateways/reports';

    protected string $view = 'filament.admin.blank';
}
