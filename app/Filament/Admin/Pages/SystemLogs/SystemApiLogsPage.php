<?php

namespace App\Filament\Admin\Pages\SystemLogs;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SystemApiLogsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCodeBracket;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SystemLogs;

    protected static ?string $navigationLabel = 'API Logs';

    protected static ?string $title = 'System API Logs';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'system-logs/api';

    protected string $view = 'filament.admin.blank';
}
