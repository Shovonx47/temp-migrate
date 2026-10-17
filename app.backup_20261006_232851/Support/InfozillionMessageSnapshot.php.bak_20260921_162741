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
    ): array {
        if ($gateway->driver !== GatewayDriver::Infozillion) {
            return [];
        }

        $credentials = $gateway->credentials ?? [];

        return [
            'bill_msisdn' => self::stringValue($credentials['bill_msisdn'] ?? null),
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
