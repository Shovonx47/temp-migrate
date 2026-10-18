<?php

declare(strict_types=1);

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum KycStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case ResubmitRequired = 'resubmit_required';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::UnderReview => 'Under Review',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
            self::ResubmitRequired => 'Resubmission Required',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'gray',
            self::UnderReview => 'warning',
            self::Verified => 'success',
            self::Rejected => 'danger',
            self::ResubmitRequired => 'info',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Pending => Heroicon::OutlinedClock,
            self::UnderReview => Heroicon::OutlinedEye,
            self::Verified => Heroicon::OutlinedCheckBadge,
            self::Rejected => Heroicon::OutlinedXCircle,
            self::ResubmitRequired => Heroicon::OutlinedArrowPath,
        };
    }

    public function isVerified(): bool
    {
        return $this === self::Verified;
    }

    public function isUnderReview(): bool
    {
        return $this === self::UnderReview;
    }

    public function isResubmitRequired(): bool
    {
        return $this === self::ResubmitRequired;
    }
}
