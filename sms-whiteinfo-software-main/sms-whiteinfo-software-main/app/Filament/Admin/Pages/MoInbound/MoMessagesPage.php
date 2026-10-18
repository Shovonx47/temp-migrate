<?php

namespace App\Filament\Admin\Pages\MoInbound;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MoMessagesPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::MoInbound;

    protected static ?string $navigationLabel = 'MO Messages';

    protected static ?string $title = 'MO Messages';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'mo/messages';

    protected string $view = 'filament.admin.blank';
}
