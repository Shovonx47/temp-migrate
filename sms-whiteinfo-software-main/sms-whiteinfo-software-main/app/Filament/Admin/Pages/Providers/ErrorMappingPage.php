<?php

namespace App\Filament\Admin\Pages\Providers;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ErrorMappingPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationCircle;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Providers;

    protected static ?string $navigationLabel = 'Error Mapping';

    protected static ?string $title = 'Error Mapping';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'providers/error-mapping';

    protected string $view = 'filament.admin.blank';
}
