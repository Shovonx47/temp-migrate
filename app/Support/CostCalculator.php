<?php

namespace App\Support;

class CostCalculator
{
    // PLACEHOLDER rates — replace with real gateway pricing later
    protected const GSM_RATE = 0.30;
    protected const UNICODE_RATE = 0.50;

    public static function calculate(?string $body, int $recipientCount): array
    {
        $body ??= '';
        $charCount = mb_strlen($body);
        $isUnicode = (bool) preg_match('/[^\x00-\x7F]/u', $body);

        $maxPerPart = $isUnicode ? 70 : 160;
        $parts = $charCount > 0 ? (int) ceil($charCount / $maxPerPart) : 1;
        $ratePerPart = $isUnicode ? self::UNICODE_RATE : self::GSM_RATE;

        $perRecipient = $parts * $ratePerPart;
        $total = $perRecipient * $recipientCount;

        return [
            'char_count' => $charCount,
            'is_unicode' => $isUnicode,
            'parts' => $parts,
            'max_per_part' => $maxPerPart,
            'per_recipient' => round($perRecipient, 4),
            'total' => round($total, 4),
            'recipient_count' => $recipientCount,
        ];
    }
}
