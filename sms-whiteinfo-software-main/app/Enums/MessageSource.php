<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum MessageSource: string implements HasColor, HasIcon, HasLabel
{
    case Web = 'web';
    case API = 'api';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Web => 'gray',
            self::API => 'info',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Web => Heroicon::OutlinedGlobeAlt,
            self::API => Heroicon::OutlinedCodeBracket,
        };
    }
}
