<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum GatewayDriver: string implements HasIcon, HasLabel
{
    case Log = 'log';
    case ReveSMS = 'revesms';
    case QSMS = 'qsms';
    case Anbernet = 'anbernet';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            GatewayDriver::Log => Heroicon::OutlinedDocumentText,
            GatewayDriver::ReveSMS => BrandIcon::ReveSMS,
            GatewayDriver::QSMS => Heroicon::OutlinedDevicePhoneMobile,
            GatewayDriver::Anbernet => BrandIcon::Anbernet,
        };
    }
}
