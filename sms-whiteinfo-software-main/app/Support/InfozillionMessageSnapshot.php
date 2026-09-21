<?php

namespace App\Support;

use App\Enums\GatewayDriver;
use App\Models\Gateway;

/**
 * Creates the immutable Infozillion fields required for an SMS transaction report.
 *
 * Values in this snapshot are taken from the gateway configuration and the provider's
 * response at the time the request is sent. They are deliberately not recalculated
 * from a gateway's later configuration.
 */
final class InfozillionMessageSnapshot
{
    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    public static function fromResponse(
        Gateway $gateway,
        array $response,
        string $requestId,
        string $defaultSmsType,
        ?string $from = null,
    ): array {
        if ($gateway->driver !== GatewayDriver::Infozillion) {
            return [];
        }

        $credentials = $gateway->credentials ?? [];

        $billMsisdn = self::resolveBillMsisdn($from, $credentials['bill_msisdn'] ?? null);

        return [
            'bill_msisdn' => $billMsisdn,
            'sms_type' => strtoupper((string) ($credentials['transaction_type'] ?? $defaultSmsType)),
            'channel' => strtoupper($gateway->smsApiType()),
            'request_id' => $requestId,
            'server_txn_id' => self::stringValue($response['serverTxnId'] ?? null),
            'server_response_code' => self::stringValue($response['serverResponseCode'] ?? null),
            'mno_txn_id' => self::stringValue($response['mnoTxnId'] ?? null),
            'mno_response_code' => self::stringValue($response['mnoResponseCode'] ?? null),
            'mno_response_message' => self::stringValue($response['mnoResponseMessage'] ?? null),
            'provider_response' => $response === [] ? null : $response,
        ];
    }

    public static function resolveBillMsisdn(?string $from, mixed $gatewayBillMsisdn): ?string
    {
        if ($from !== null && $from !== '') {
            if (! preg_match('/[a-zA-Z]/', $from)) {
                $digits = preg_replace('/\D+/', '', $from) ?? '';
                if ($digits !== '') {
                    if (str_starts_with($digits, '880') && strlen($digits) >= 11 && strlen($digits) <= 15) {
                        return $digits;
                    }
                    if (str_starts_with($digits, '0') && strlen($digits) >= 10 && strlen($digits) <= 13) {
                        return '880'.substr($digits, 1);
                    }
                    if (strlen($digits) >= 9 && strlen($digits) <= 12) {
                        return '880'.$digits;
                    }

                    return $digits;
                }
            }
        }

        return self::stringValue($gatewayBillMsisdn);
    }

    private static function stringValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        return null;
    }
}
