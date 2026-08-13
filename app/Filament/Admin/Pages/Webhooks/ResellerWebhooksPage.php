<?php

namespace App\Filament\Admin\Pages\Webhooks;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ResellerWebhooksPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Webhooks;

    protected static ?string $navigationLabel = 'Reseller Webhooks';

    protected static ?string $title = 'Reseller Webhooks';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'webhooks/reseller';

    protected string $view = 'filament.admin.blank';
}
