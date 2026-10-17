<?php

namespace App\Filament\Admin\Pages\SmsManagement;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SmsInquiryPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SmsManagement;

    protected static ?string $navigationLabel = 'SMS Inquiry';

    protected static ?string $title = 'SMS Inquiry';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'sms/inquiry';

    protected string $view = 'filament.admin.blank';
}
