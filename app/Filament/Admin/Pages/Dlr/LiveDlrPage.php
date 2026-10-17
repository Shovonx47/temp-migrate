<?php

namespace App\Filament\Admin\Pages\Dlr;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class LiveDlrPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Dlr;

    protected static ?string $navigationLabel = 'Live DLR';

    protected static ?string $title = 'Live DLR';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'dlr/live';

    protected string $view = 'filament.admin.blank';
}
