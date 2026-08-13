<?php

namespace App\Filament\Admin\Pages\Webhooks;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class WebhookConfigurationPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Webhooks;

    protected static ?string $navigationLabel = 'Webhook Configuration';

    protected static ?string $title = 'Webhook Configuration';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'webhooks/configuration';

    protected string $view = 'filament.admin.blank';
}
