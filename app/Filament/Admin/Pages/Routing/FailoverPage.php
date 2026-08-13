<?php

namespace App\Filament\Admin\Pages\Routing;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class FailoverPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Routing;

    protected static ?string $navigationLabel = 'Failover';

    protected static ?string $title = 'Failover';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'routing/failover';

    protected string $view = 'filament.admin.blank';
}
