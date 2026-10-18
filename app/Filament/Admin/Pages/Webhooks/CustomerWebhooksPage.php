<?php

namespace App\Filament\Admin\Pages\Webhooks;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CustomerWebhooksPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Webhooks;

    protected static ?string $navigationLabel = 'Customer Webhooks';

    protected static ?string $title = 'Customer Webhooks';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'webhooks/customer';

    protected string $view = 'filament.admin.blank';
}
