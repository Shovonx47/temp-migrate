<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum AdminNavigationGroup: string implements HasLabel, HasIcon
{
    case User = 'user';
    case Messaging = 'messaging';
    case Audience = 'audience';
    case Billing = 'billing';
    case Settings = 'settings';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Settings => 'System Settings',
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
            self::Billing => Heroicon::OutlinedCreditCard,
            self::Settings => Heroicon::OutlinedCog,
        };
    }
}
