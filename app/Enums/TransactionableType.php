<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum TransactionableType: string implements HasColor, HasIcon, HasLabel
{
    case Deposit = 'deposit';
    case Campaign = 'campaign';
    case Message = 'message';
    case MessageBatch = 'message_batch';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::MessageBatch => 'Message Batch',
            default => $this->name,
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Deposit => 'success',
            self::Campaign => 'primary',
            self::Message => 'info',
            self::MessageBatch => 'info',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Deposit => Heroicon::OutlinedBanknotes,
            self::Campaign => Heroicon::OutlinedMegaphone,
            self::Message => Heroicon::OutlinedEnvelope,
            self::MessageBatch => Heroicon::OutlinedEnvelope,
        };
    }
}
