<?php

namespace App\Filament\Admin\Pages\Webhooks;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class WebhookLogsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Webhooks;

    protected static ?string $navigationLabel = 'Webhook Logs';

    protected static ?string $title = 'Webhook Logs';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'webhooks/logs';

    protected string $view = 'filament.admin.blank';
}
