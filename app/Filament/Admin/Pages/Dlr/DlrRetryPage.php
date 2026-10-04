<?php

namespace App\Filament\Admin\Pages\Dlr;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class DlrRetryPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Dlr;

    protected static ?string $navigationLabel = 'DLR Retry';

    protected static ?string $title = 'DLR Retry';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'dlr/retry';

    protected string $view = 'filament.admin.blank';
}
