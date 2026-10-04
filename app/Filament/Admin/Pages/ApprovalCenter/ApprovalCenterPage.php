<?php

namespace App\Filament\Admin\Pages\ApprovalCenter;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ApprovalCenterPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::ApprovalCenter;

    protected static ?string $navigationLabel = 'Approval Overview';

    protected static ?string $title = 'Approval Center';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'approvals/overview';

    protected string $view = 'filament.admin.blank';
}
