<?php

namespace App\Filament\Admin\Pages\ApprovalCenter;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PendingCustomerApprovalPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::ApprovalCenter;

    protected static ?string $navigationLabel = 'Pending Customers';

    protected static ?string $title = 'Pending Customer Approval';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'approvals/customers';

    protected string $view = 'filament.admin.blank';
}
