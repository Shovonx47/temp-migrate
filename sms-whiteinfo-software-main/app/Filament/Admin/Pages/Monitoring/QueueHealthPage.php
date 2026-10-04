<?php

namespace App\Filament\Admin\Pages\Monitoring;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class QueueHealthPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Monitoring;

    protected static ?string $navigationLabel = 'Queue Health';

    protected static ?string $title = 'Queue Health';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'monitoring/queue-health';

    protected string $view = 'filament.admin.blank';
}
