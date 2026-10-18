<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum SpNavigationGroup: string implements HasIcon, HasLabel
{
    case Customers = 'customers';
    case Billing = 'billing';
    case Messaging = 'messaging';
    case Employees = 'employees';
    case Reports = 'reports';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Customers => 'Customers',
            self::Billing => 'Billing',
            self::Messaging => 'Messaging',
            self::Employees => 'Employees',
            self::Reports => 'Reports',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Customers => Heroicon::OutlinedUsers,
            self::Billing => Heroicon::OutlinedBanknotes,
            self::Messaging => Heroicon::OutlinedChatBubbleLeftRight,
            self::Employees => Heroicon::OutlinedUserGroup,
            self::Reports => Heroicon::OutlinedChartBar,
        };
    }
}
