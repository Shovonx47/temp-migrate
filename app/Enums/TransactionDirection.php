<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum TransactionDirection: string implements HasColor, HasIcon, HasLabel
{
    case Credit = 'credit';
    case Debit = 'debit';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Credit => 'success',
            self::Debit => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Credit => Heroicon::OutlinedArrowDown,
            self::Debit => Heroicon::OutlinedArrowUp,
        };
    }
}
