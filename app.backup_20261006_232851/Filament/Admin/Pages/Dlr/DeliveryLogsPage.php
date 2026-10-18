<?php

namespace App\Filament\Admin\Pages\Dlr;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class DeliveryLogsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Dlr;

    protected static ?string $navigationLabel = 'Delivery Logs';

    protected static ?string $title = 'Delivery Logs';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'dlr/logs';

    protected string $view = 'filament.admin.blank';
}
