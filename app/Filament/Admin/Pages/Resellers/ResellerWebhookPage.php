<?php

namespace App\Filament\Admin\Pages\Resellers;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ResellerWebhookPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $navigationLabel = 'Reseller Webhook';

    protected static ?string $title = 'Reseller Webhook';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'resellers/webhook';

    protected string $view = 'filament.admin.blank';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
