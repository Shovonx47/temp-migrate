<?php

namespace App\Filament\Admin\Pages\Resellers;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ResellerAuditPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocument;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $navigationLabel = 'Reseller Audit';

    protected static ?string $title = 'Reseller Audit';

    protected static ?int $navigationSort = 14;

    protected static ?string $slug = 'resellers/audit';

    protected string $view = 'filament.admin.blank';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
