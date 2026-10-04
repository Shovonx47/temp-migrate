<?php

namespace App\Filament\Admin\Pages\Providers;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ProviderApiLogsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Providers;

    protected static ?string $navigationLabel = 'Provider API Logs';

    protected static ?string $title = 'Provider API Logs';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'providers/api-logs';

    protected string $view = 'filament.admin.blank';
}
