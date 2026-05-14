<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum SenderType: string implements HasColor, HasIcon, HasLabel
{
    case Numeric = 'numeric';
    case AlphaNumeric = 'alphanumeric';
    case Shortcode = 'shortcode';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Numeric => 'Numeric',
            self::AlphaNumeric => 'Alpha Numeric',
            self::Shortcode => 'Shortcode',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Numeric => 'primary',
            self::AlphaNumeric => 'success',
            self::Shortcode => 'warning',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Numeric => Heroicon::OutlinedHashtag,
            self::AlphaNumeric => Heroicon::OutlinedLanguage,
            self::Shortcode => Heroicon::OutlinedBolt,
        };
    }
}
