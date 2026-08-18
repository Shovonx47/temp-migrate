<?php

namespace App\Filament\Admin\Pages\SenderCli;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MaskingListPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SenderCli;

    protected static ?string $navigationLabel = 'Masking List';

    protected static ?string $title = 'Masking Senders';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'sender-cli/masking';

    protected string $view = 'filament.admin.sender-cli.masking-list';
}
