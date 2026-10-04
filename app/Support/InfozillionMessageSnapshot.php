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
        ?string $senderCli = null,
    ): array {
        if ($gateway->driver !== GatewayDriver::Infozillion) {
            return [];
        }

        $credentials = $gateway->credentials ?? [];

        return [
            'bill_msisdn' => self::snapshotBillMsisdn($credentials, $senderCli),
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

    /**
     * Derives the bill_msisdn that was actually sent in the API payload.
     *
     * For IPTSP with a numeric CLI, the driver sets billMsisdn = CLI in the request
     * payload (see InfozillionDriver::buildSendPayload). This snapshot must mirror
     * that logic so the report shows the real value used — not the gateway's
     * configured bill_msisdn which may be a different number.
     *
     * For masking / alphanumeric CLIs or non-IPTSP, the gateway's configured
     * bill_msisdn is the correct value.
     */
    private static function snapshotBillMsisdn(array $credentials, ?string $senderCli): ?string
    {
        $apiType = strtolower(trim((string) ($credentials['api_type'] ?? '')));

        // For IPTSP with a numeric sender CLI, billMsisdn must equal the CLI.
        // This mirrors the exact logic in InfozillionDriver::buildSendPayload().
        if ($apiType === 'iptsp' && $senderCli !== null && $senderCli !== '') {
            $cliDigits = preg_replace('/\D+/', '', $senderCli) ?? '';
            $isNumericCli = $cliDigits !== '' && ! preg_match('/[a-zA-Z]/', $senderCli);

            if ($isNumericCli) {
                if (str_starts_with($cliDigits, '0')) {
                    $cliDigits = '880' . substr($cliDigits, 1);
                } elseif (! str_starts_with($cliDigits, '880')) {
                    $cliDigits = '880' . $cliDigits;
                }

                return $cliDigits;
            }
        }

        return self::stringValue($credentials['bill_msisdn'] ?? null);
    }
}

