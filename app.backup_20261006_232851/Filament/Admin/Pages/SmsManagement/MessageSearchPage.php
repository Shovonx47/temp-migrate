<?php

namespace App\Filament\Admin\Pages\SmsManagement;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MessageSearchPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SmsManagement;

    protected static ?string $navigationLabel = 'Message Search';

    protected static ?string $title = 'Message Search';

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'sms/message-search';

    protected string $view = 'filament.admin.blank';
}
