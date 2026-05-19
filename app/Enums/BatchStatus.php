<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum BatchStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Processing => 'primary',
            self::Completed => 'success',
            self::Failed => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Pending => Heroicon::OutlinedClock,
            self::Processing => Heroicon::OutlinedPlay,
            self::Completed => Heroicon::OutlinedCheckCircle,
            self::Failed => Heroicon::OutlinedXCircle,
        };
    }

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }
}
