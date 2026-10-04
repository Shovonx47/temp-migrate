<?php

namespace App\Filament\Admin\Pages\ApiManagement;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ApiLogsMgmtPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::ApiManagement;

    protected static ?string $navigationLabel = 'API Logs';

    protected static ?string $title = 'API Logs';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'api-management/logs';

    protected string $view = 'filament.admin.blank';
}
