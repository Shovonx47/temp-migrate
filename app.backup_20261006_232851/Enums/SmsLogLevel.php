<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum SmsLogLevel: string implements HasColor, HasIcon, HasLabel
{
    case Debug = 'debug';
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Debug => 'gray',
            self::Info => 'info',
            self::Warning => 'warning',
            self::Error => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Debug => Heroicon::OutlinedBug,
            self::Info => Heroicon::OutlinedInformationCircle,
            self::Warning => Heroicon::OutlinedExclamationTriangle,
            self::Error => Heroicon::OutlinedXCircle,
        };
    }

    public function terminalColor(): string
    {
        return match ($this) {
            self::Debug => '#6B7280',  // gray
            self::Info => '#22D3EE',   // cyan
            self::Warning => '#FBBF24', // amber
            self::Error => '#F87171',   // red
        };
    }
}
