<?php

namespace App\Filament\Admin\Pages\SenderCli;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SenderResolutionPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SenderCli;

    protected static ?string $navigationLabel = 'Sender Resolution';

    protected static ?string $title = 'Sender Resolution';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'sender-cli/resolution';

    protected string $view = 'filament.admin.sender-cli.sender-resolution';
}
