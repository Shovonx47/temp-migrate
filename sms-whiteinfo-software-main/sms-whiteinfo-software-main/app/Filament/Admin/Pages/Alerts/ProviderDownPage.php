<?php

namespace App\Filament\Admin\Pages\Alerts;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ProviderDownPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Alerts;

    protected static ?string $navigationLabel = 'Provider Down Alerts';

    protected static ?string $title = 'Provider Down Alerts';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'alerts/provider-down';

    protected string $view = 'filament.admin.blank';
}
