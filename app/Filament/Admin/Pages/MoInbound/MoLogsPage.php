<?php

namespace App\Filament\Admin\Pages\MoInbound;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MoLogsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::MoInbound;

    protected static ?string $navigationLabel = 'MO Logs';

    protected static ?string $title = 'MO Logs';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'mo/logs';

    protected string $view = 'filament.admin.blank';
}
