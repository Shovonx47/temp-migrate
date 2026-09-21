<?php

namespace App\Filament\Admin\Pages\SmsManagement;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class FailedSmsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationCircle;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SmsManagement;

    protected static ?string $navigationLabel = 'Failed SMS';

    protected static ?string $title = 'Failed SMS';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'sms/failed';

    protected string $view = 'filament.admin.blank';
}
