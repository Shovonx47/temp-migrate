<?php

namespace App\Filament\Admin\Pages\Billing;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class InvoicesPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Billing;

    protected static ?string $navigationLabel = 'Invoices';

    protected static ?string $title = 'Invoices';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.admin.billing.placeholder';
}
