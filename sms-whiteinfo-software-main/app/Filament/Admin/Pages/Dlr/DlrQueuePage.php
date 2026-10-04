<?php

namespace App\Filament\Admin\Pages\Dlr;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class DlrQueuePage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Dlr;

    protected static ?string $navigationLabel = 'DLR Queue';

    protected static ?string $title = 'DLR Queue';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'dlr/queue';

    protected string $view = 'filament.admin.blank';
}
