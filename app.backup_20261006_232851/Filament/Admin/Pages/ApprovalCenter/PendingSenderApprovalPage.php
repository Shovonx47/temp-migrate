<?php

namespace App\Filament\Admin\Pages\ApprovalCenter;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PendingSenderApprovalPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::ApprovalCenter;

    protected static ?string $navigationLabel = 'Pending Senders';

    protected static ?string $title = 'Pending Sender Approval';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'approvals/senders';

    protected string $view = 'filament.admin.blank';
}
