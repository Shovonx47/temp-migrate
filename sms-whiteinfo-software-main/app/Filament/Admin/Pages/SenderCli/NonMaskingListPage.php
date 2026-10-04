<?php

namespace App\Filament\Admin\Pages\SenderCli;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class NonMaskingListPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SenderCli;

    protected static ?string $navigationLabel = 'Non-Masking Sender';

    protected static ?string $title = 'Non-Masking Senders';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'sender-cli/non-masking';

    protected string $view = 'filament.admin.sender-cli.non-masking-list';
}
