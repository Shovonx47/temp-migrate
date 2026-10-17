<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum TariffPlanStatus: string implements HasColor, HasIcon, HasLabel
{
    case Active = 'active';
    case Draft = 'draft';
    case Archived = 'archived';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => 'success',
            self::Draft => 'warning',
            self::Archived => 'gray',
        };
    }

    public function getIcon(): string|Heroicon|Htmlable|null
    {
        return match ($this) {
            self::Active => Heroicon::OutlinedCheckCircle,
            self::Draft => Heroicon::OutlinedPencilSquare,
            self::Archived => Heroicon::OutlinedArchiveBox,
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function canBeArchived(): bool
    {
        return $this !== self::Archived;
    }
}
