<?php

namespace App\Filament\Admin\Pages\Kyc;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class KycAuditPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocument;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Kyc;

    protected static ?string $navigationLabel = 'KYC Audit';

    protected static ?string $title = 'KYC Audit';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'kyc/audit';

    protected string $view = 'filament.admin.blank';
}
