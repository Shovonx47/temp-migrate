<?php

namespace App\Filament\Admin\Pages\SystemLogs;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SystemSmsLogsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SystemLogs;

    protected static ?string $navigationLabel = 'SMS Logs';

    protected static ?string $title = 'SMS Logs';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'system-logs/sms';

    protected string $view = 'filament.admin.system-logs.sms-logs';
}
