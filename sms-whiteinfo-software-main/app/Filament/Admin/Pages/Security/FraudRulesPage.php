<?php

namespace App\Filament\Admin\Pages\Security;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class FraudRulesPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Security;

    protected static ?string $navigationLabel = 'Fraud Rules';

    protected static ?string $title = 'Fraud Rules';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'security/fraud-rules';

    protected string $view = 'filament.admin.blank';
}
