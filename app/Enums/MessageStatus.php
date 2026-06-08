<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum MessageStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Sending = 'sending';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Sending => 'primary',
            self::Sent => 'info',
            self::Delivered => 'success',
            self::Failed => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Pending => Heroicon::OutlinedClock,
            self::Sending => Heroicon::OutlinedPlay,
            self::Sent => Heroicon::OutlinedPaperAirplane,
            self::Delivered => Heroicon::OutlinedCheckBadge,
            self::Failed => Heroicon::OutlinedXCircle,
        };
    }
}
