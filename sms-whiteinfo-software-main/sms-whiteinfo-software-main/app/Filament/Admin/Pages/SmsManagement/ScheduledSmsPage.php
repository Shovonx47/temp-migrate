<?php

namespace App\Filament\Admin\Pages\SmsManagement;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ScheduledSmsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SmsManagement;

    protected static ?string $navigationLabel = 'Scheduled SMS';

    protected static ?string $title = 'Scheduled SMS';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'sms/scheduled';

    protected string $view = 'filament.admin.blank';
}
