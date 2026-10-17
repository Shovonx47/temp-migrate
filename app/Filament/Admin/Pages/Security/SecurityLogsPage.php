<?php

namespace App\Filament\Admin\Pages\Security;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SecurityLogsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Security;

    protected static ?string $navigationLabel = 'Security Logs';

    protected static ?string $title = 'Security Logs';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'security/logs';

    protected string $view = 'filament.admin.blank';
}
