<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum UserType: string implements HasColor, HasIcon, HasLabel
{
    case Superman = 'superman';
    case Customer = 'customer';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Superman => 'danger',
            self::Customer => 'primary',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Superman => Heroicon::OutlinedShieldCheck,
            self::Customer => Heroicon::OutlinedUser,
        };
    }

    public function isSuperman(): bool
    {
        return $this === self::Superman;
    }

    public function isCustomer(): bool
    {
        return $this === self::Customer;
    }
}
