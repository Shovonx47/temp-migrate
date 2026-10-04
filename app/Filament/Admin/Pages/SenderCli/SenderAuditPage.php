<?php

namespace App\Filament\Admin\Pages\SenderCli;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SenderAuditPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocument;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SenderCli;

    protected static ?string $navigationLabel = 'Sender Audit';

    protected static ?string $title = 'Sender Audit';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'sender-cli/audit';

    protected string $view = 'filament.admin.sender-cli.sender-audit';
}
