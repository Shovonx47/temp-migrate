<?php

namespace App\Filament\Admin\Pages\Providers;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ProviderHealthPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServer;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Providers;

    protected static ?string $navigationLabel = 'Provider Health';

    protected static ?string $title = 'Provider Health';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'providers/health';

    protected string $view = 'filament.admin.blank';
}
