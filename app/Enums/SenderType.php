<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum SenderType: string implements HasColor, HasIcon, HasLabel
{
    case Numeric = 'numeric';
    case AlphaNumeric = 'alphanumeric';
    case Shortcode = 'shortcode';

    /**
     * Numeric sender IDs are non-masking (just a phone number). Alphanumeric and
     * shortcode senders carry a brand name and are billed at the masking rate.
     */
    public const string MaskingCategory = 'masking';

    public const string NonMaskingCategory = 'non-masking';

    public function isMasking(): bool
    {
        return $this !== self::Numeric;
    }

    /**
     * @return array<string, string>
     */
    public static function maskingCategoryOptions(): array
    {
        return [
            self::MaskingCategory => 'Masking',
            self::NonMaskingCategory => 'Non-masking',
        ];
    }

    public function maskingCategory(): string
    {
        return $this->isMasking() ? self::MaskingCategory : self::NonMaskingCategory;
    }

    public static function fromMaskingCategory(string $category): self
    {
        return match (strtolower(str_replace('_', '-', trim($category)))) {
            'non-masking', 'numeric', 'number' => self::Numeric,
            'shortcode', 'short-code' => self::Shortcode,
            'masking', 'alphanumeric', 'alpha-numeric', 'alpha' => self::AlphaNumeric,
            default => self::AlphaNumeric,
        };
    }

    public static function normalizeStoredValue(mixed $value): ?string
    {
        if ($value instanceof self) {
            return $value->value;
        }

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (self::tryFrom($trimmed) !== null) {
            return $trimmed;
        }

        return self::fromMaskingCategory($trimmed)->value;
    }

    public function getLabel(): string|Htmlable|null
    {
        return $this->isMasking() ? 'Masking' : 'Non-masking';
    }

    public function getColor(): string|array|null
    {
        return $this->isMasking() ? 'success' : 'primary';
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return $this->isMasking()
            ? Heroicon::OutlinedLanguage
            : Heroicon::OutlinedHashtag;
    }
}
