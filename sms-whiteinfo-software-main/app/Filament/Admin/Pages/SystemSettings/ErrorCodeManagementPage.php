<?php

namespace App\Filament\Admin\Pages\SystemSettings;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ErrorCodeManagementPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SystemSettings;

    protected static ?string $navigationLabel = 'Error Code Management';

    protected static ?string $title = 'Error Code Management';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'system-settings/error-codes';

    protected string $view = 'filament.admin.blank';
}
