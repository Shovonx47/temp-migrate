<?php

namespace App\Filament\Admin\Pages\Security;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SecurityDashboardPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Security;

    protected static ?string $navigationLabel = 'Security Dashboard';

    protected static ?string $title = 'Security Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'security/dashboard';

    protected string $view = 'filament.admin.blank';
}
