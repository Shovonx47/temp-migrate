<?php

namespace App\Filament\Admin\Pages\PaymentGateway;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ReconciliationPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|\UnitEnum|null $navigationGroup = 'PaymentGateway';

    protected static ?string $navigationLabel = 'Reconciliation';

    protected static ?string $title = 'Reconciliation';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'payment-gateways/reconciliation';

    protected string $view = 'filament.admin.blank';
}
