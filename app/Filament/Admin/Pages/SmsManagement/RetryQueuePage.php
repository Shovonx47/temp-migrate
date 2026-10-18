<?php

namespace App\Filament\Admin\Pages\SmsManagement;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class RetryQueuePage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SmsManagement;

    protected static ?string $navigationLabel = 'Retry Queue';

    protected static ?string $title = 'Retry Queue';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'sms/retry-queue';

    protected string $view = 'filament.admin.blank';
}
