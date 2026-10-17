<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum BatchSource: string implements HasColor, HasIcon, HasLabel
{
    case Manual = 'manual';
    case Group = 'group';
    case Upload = 'upload';
    case API = 'api';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Manual => 'primary',
            self::Group => 'success',
            self::Upload => 'warning',
            self::API => 'info',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Manual => Heroicon::OutlinedPencilSquare,
            self::Group => Heroicon::OutlinedUsers,
            self::Upload => Heroicon::OutlinedArrowUpTray,
            self::API => Heroicon::OutlinedCodeBracket,
        };
    }
}
