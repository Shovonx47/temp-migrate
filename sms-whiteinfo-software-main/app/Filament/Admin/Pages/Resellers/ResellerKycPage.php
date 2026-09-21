<?php

namespace App\Filament\Admin\Pages\Resellers;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ResellerKycPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $navigationLabel = 'Reseller KYC';

    protected static ?string $title = 'Reseller KYC';

    protected static ?int $navigationSort = 13;

    protected static ?string $slug = 'resellers/kyc';

    protected string $view = 'filament.admin.blank';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
