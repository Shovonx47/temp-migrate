<?php

namespace App\Filament\Admin\Pages\ApprovalCenter;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PendingResellerApprovalPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::ApprovalCenter;

    protected static ?string $navigationLabel = 'Pending Resellers';

    protected static ?string $title = 'Pending Reseller Approval';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'approvals/resellers';

    protected string $view = 'filament.admin.blank';
}
