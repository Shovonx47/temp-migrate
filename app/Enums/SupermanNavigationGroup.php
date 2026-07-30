<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum SupermanNavigationGroup: string implements HasIcon, HasLabel
{
    case Analytics = 'analytics';
    case User = 'user';
    case Messaging = 'messaging';
    case Audience = 'audience';
    case Finance = 'finance';
    case Settings = 'settings';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::User => 'User Management',
            default => $this->name
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::User => Heroicon::OutlinedUser,
            self::Messaging => Heroicon::OutlinedChatBubbleLeftRight,
            self::Audience => Heroicon::OutlinedUsers,
            self::Finance => Heroicon::OutlinedBanknotes,
            self::Analytics => Heroicon::OutlinedChartBar,
            self::Settings => Heroicon::OutlinedCog,
        };
    }
}
