<?php

namespace App\Filament\Admin\Pages\Webhooks;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class FailedWebhooksPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationCircle;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Webhooks;

    protected static ?string $navigationLabel = 'Failed Webhooks';

    protected static ?string $title = 'Failed Webhooks';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'webhooks/failed';

    protected string $view = 'filament.admin.blank';
}
