<?php

namespace App\Filament\Admin\Pages\SystemLogs;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SystemLogsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SystemLogs;

    protected static ?string $navigationLabel = 'System Logs';

    protected static ?string $title = 'System Logs';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'system-logs/all';

    protected string $view = 'filament.admin.blank';
}
