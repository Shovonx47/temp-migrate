<?php

namespace App\Filament\Admin\Pages\Providers;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class DlrMappingPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Providers;

    protected static ?string $navigationLabel = 'DLR Mapping';

    protected static ?string $title = 'DLR Mapping';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'providers/dlr-mapping';

    protected string $view = 'filament.admin.blank';
}
