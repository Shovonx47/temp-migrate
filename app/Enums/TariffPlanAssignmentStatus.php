<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum TariffPlanAssignmentStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Superseded = 'superseded';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => 'success',
            self::Superseded => 'gray',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
