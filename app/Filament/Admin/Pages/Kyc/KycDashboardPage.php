<?php

namespace App\Filament\Admin\Pages\Kyc;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class KycDashboardPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Kyc;

    protected static ?string $navigationLabel = 'KYC Dashboard';

    protected static ?string $title = 'KYC Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'kyc/dashboard';

    protected string $view = 'filament.admin.blank';
}
