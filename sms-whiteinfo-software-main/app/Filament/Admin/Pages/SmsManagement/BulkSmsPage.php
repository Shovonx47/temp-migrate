<?php

namespace App\Filament\Admin\Pages\SmsManagement;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class BulkSmsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SmsManagement;

    protected static ?string $navigationLabel = 'Bulk SMS';

    protected static ?string $title = 'Bulk SMS';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'sms/bulk';

    protected string $view = 'filament.admin.blank';
}
