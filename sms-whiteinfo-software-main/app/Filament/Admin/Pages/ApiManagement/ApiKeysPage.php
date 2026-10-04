<?php

namespace App\Filament\Admin\Pages\ApiManagement;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ApiKeysPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::ApiManagement;

    protected static ?string $navigationLabel = 'API Keys';

    protected static ?string $title = 'API Keys';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'api-management/keys';

    protected string $view = 'filament.admin.blank';
}
