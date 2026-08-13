<?php

namespace App\Filament\Admin\Pages\SenderCli;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SenderPolicyPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SenderCli;

    protected static ?string $navigationLabel = 'Sender Policy';

    protected static ?string $title = 'Sender Policy';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'sender-cli/policy';

    protected string $view = 'filament.admin.blank';
}
