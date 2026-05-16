<?php

namespace App\Services\Sms;

/**
 * Calculates the parts/encoding/character count for an SMS body so we can
 * price it consistently before sending. Mirrors the JS implementation used
 * in the compose form (resources/js/forms/componenets/sms-stats.ts) — keep
 * the two in sync if the character tables change.
 */
final readonly class MessageMetrics
{
    public const string GSM7 = 'GSM7';

    public const string UCS2 = 'UCS2';

    private const int GSM7_SINGLE = 160;

    private const int GSM7_MULTI = 153;

    private const int UCS2_SINGLE = 70;

    private const int UCS2_MULTI = 67;

    public function __construct(
        public string $encoding,
        public int $chars,
        public int $parts,
    ) {}

    public static function for(string $body): self
    {
        if ($body === '') {
            return new self(self::GSM7, 0, 1);
        }

        $basic = self::gsm7BasicSet();
        $extension = self::gsm7ExtensionSet();

        $count = 0;
        $isGsm7 = true;

        foreach (mb_str_split($body) as $char) {
            if (isset($basic[$char])) {
                $count++;
            } elseif (isset($extension[$char])) {
                $count += 2;
            } else {
                $isGsm7 = false;
                break;
            }
        }

        if (! $isGsm7) {
            $length = mb_strlen($body);

            return new self(
                encoding: self::UCS2,
                chars: $length,
                parts: $length <= self::UCS2_SINGLE
                    ? 1
                    : (int) ceil($length / self::UCS2_MULTI),
            );
        }

        return new self(
            encoding: self::GSM7,
            chars: $count,
            parts: $count <= self::GSM7_SINGLE
                ? 1
                : (int) ceil($count / self::GSM7_MULTI),
        );
    }

    /**
     * @return array<string, true>
     */
    private static function gsm7BasicSet(): array
    {
        static $set = null;

        if ($set === null) {
            $set = array_fill_keys(
                mb_str_split("@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ ÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà"),
                true,
            );
        }

        return $set;
    }

    /**
     * @return array<string, true>
     */
    private static function gsm7ExtensionSet(): array
    {
        static $set = null;

        if ($set === null) {
            $set = array_fill_keys(
                mb_str_split("\f^{}\\[~]|€"),
                true,
            );
        }

        return $set;
    }
}
