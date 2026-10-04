<?php

namespace App\Filament\Admin\Pages\Affiliates;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AffiliateAuditPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocument;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Affiliates;

    protected static ?string $navigationLabel = 'Affiliate Audit';

    protected static ?string $title = 'Affiliate Audit';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'affiliates/audit';

    protected string $view = 'filament.admin.blank';
}
