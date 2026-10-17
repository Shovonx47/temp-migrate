<?php

namespace App\Support;

readonly class MnoCarrier
{
    private const array BILL_MSISDN_TO_MNO = [
        '88013' => 'GP',
        '88017' => 'GP',
        '88014' => 'BL',
        '88019' => 'BL',
        '88015' => 'TT',
        '88016' => 'RB',
        '88018' => 'RB',
    ];

    private const array MNO_TO_OPERATOR_CODES = [
        'GP' => [13, 17],
        'BL' => [14, 19],
        'TT' => [15],
        'RB' => [16, 18],
    ];

    /**
     * Determine which MNO a gateway is bound to from its bill_msisdn prefix.
     */
    public static function fromBillMsisdn(string $billMsisdn): ?string
    {
        $digits = ltrim($billMsisdn, '+');

        foreach (self::BILL_MSISDN_TO_MNO as $prefix => $mno) {
            if (str_starts_with($digits, $prefix)) {
                return $mno;
            }
        }

        return null;
    }

    /**
     * Check if an E.164 phone number belongs to the given MNO carrier.
     */
    public static function matchesCarrier(string $e164Phone, string $mnoCode): bool
    {
        $digits = ltrim($e164Phone, '+');
        $prefixes = self::MNO_TO_OPERATOR_CODES[$mnoCode] ?? [];

        if (! str_starts_with($digits, '880') || $prefixes === []) {
            return false;
        }

        $operatorDigit = (int) substr($digits, 3, 2);

        return in_array($operatorDigit, $prefixes, true);
    }
}
