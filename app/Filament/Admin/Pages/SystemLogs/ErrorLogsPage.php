<?php

namespace App\Filament\Admin\Pages\SystemLogs;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ErrorLogsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationCircle;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SystemLogs;

    protected static ?string $navigationLabel = 'Error Logs';

    protected static ?string $title = 'Error Logs';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'system-logs/errors';

    protected string $view = 'filament.admin.blank';
}
