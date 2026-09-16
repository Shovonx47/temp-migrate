<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SmsReportsPage extends AbstractReportPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Reports;

    protected static ?string $navigationLabel = 'SMS Reports';

    protected static ?string $title = 'SMS Reports';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'reports/sms';

    protected function reportType(): string
    {
        return 'sms';
    }
}
