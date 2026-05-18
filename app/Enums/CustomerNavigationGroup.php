<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum CustomerNavigationGroup: string implements HasIcon, HasLabel
{
    case Messaging = 'messaging';
    case Audience = 'audience';
    case Billing = 'billing';
    case Reports = 'reports';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Messaging => Heroicon::OutlinedChatBubbleLeftRight,
            self::Audience => Heroicon::OutlinedUsers,
            self::Billing => Heroicon::OutlinedCreditCard,
            self::Reports => Heroicon::OutlinedChartBar,
        };
    }
}
