<?php

namespace App\Filament\Admin\Pages\Security;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class IpWhitelistPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Security;

    protected static ?string $navigationLabel = 'IP Whitelist';

    protected static ?string $title = 'IP Whitelist';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'security/ip-whitelist';

    protected string $view = 'filament.admin.blank';
}
