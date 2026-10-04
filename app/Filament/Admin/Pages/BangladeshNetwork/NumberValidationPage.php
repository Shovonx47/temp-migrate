<?php

namespace App\Filament\Admin\Pages\BangladeshNetwork;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class NumberValidationPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::BangladeshNetwork;

    protected static ?string $navigationLabel = 'Number Validation';

    protected static ?string $title = 'Number Validation';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'bd-network/number-validation';

    protected string $view = 'filament.admin.blank';
}
