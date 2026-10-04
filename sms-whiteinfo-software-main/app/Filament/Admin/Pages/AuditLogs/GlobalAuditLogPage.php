<?php

namespace App\Filament\Admin\Pages\AuditLogs;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class GlobalAuditLogPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocument;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::AuditLogs;

    protected static ?string $navigationLabel = 'Global Audit Log';

    protected static ?string $title = 'Global Audit Log';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'audit-logs/global';

    protected string $view = 'filament.admin.blank';
}
