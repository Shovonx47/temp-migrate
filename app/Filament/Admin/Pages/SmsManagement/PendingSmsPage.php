<?php

namespace App\Filament\Admin\Pages\SmsManagement;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PendingSmsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SmsManagement;

    protected static ?string $navigationLabel = 'Pending SMS';

    protected static ?string $title = 'Pending SMS';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'sms/pending';

    protected string $view = 'filament.admin.blank';
}
