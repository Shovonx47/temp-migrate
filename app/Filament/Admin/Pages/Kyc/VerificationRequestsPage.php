<?php

namespace App\Filament\Admin\Pages\Kyc;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class VerificationRequestsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Kyc;

    protected static ?string $navigationLabel = 'Verification Requests';

    protected static ?string $title = 'Verification Requests';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'kyc/requests';

    protected string $view = 'filament.admin.blank';
}
