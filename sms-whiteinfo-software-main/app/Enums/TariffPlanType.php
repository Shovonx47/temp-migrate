<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum TariffPlanType: string implements HasColor, HasLabel
{
    case Standard = 'standard';
    case Custom = 'custom';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Standard => 'info',
            self::Custom => 'warning',
        };
    }
}
