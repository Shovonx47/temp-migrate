<?php

namespace App\Services\Sms\Drivers;

use App\Contracts\SmsGateway;
use App\Contracts\SupportsBulkSms;
use App\Contracts\SupportsDeliveryStatus;
use App\Enums\DeliveryOutcome;
use App\Services\Sms\Data\BulkSmsRequest;
use App\Services\Sms\Data\BulkSmsResponse;
use App\Services\Sms\Data\DeliveryStatusResponse;
use App\Services\Sms\Data\SmsRequest;
use App\Services\Sms\Data\SmsResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final readonly class ReveSmsDriver implements SmsGateway, SupportsBulkSms, SupportsDeliveryStatus
{
    public function __construct(private array $credentials = []) {}

    /**
     * @throws ConnectionException
     * @throws RuntimeException
     */
    public function send(SmsRequest $request): SmsResponse
    {
        try {
            $response = $this->client()->post('sendtext', [
                'apikey' => $this->credentials['api_key'] ?? '',
                'secretkey' => $this->credentials['secret_key'] ?? '',
                'callerID' => $request->from ?? '',
                'toUser' => ltrim($request->to, '+'),
                'messageContent' => $request->body,
            ]);

            if ($response->failed() || $response->json('Status') !== '0') {
                return new SmsResponse(
                    successful: false,
                    errorMessage: $response->json('Text', 'Unknown error'),
                    raw: $response->json() ?? [],
                );
            }

            $data = $response->json() ?? [];
            $messageId = (string) ($data['Message_ID'] ?? '');

            return new SmsResponse(
                successful: true,
                gatewayMessageId: $messageId !== '' ? $messageId : null,
                raw: $data,
            );
        } catch (ConnectionException $e) {
            return new SmsResponse(
                successful: false,
                errorMessage: 'Connection failed: '.$e->getMessage(),
            );
        }
    }

    /**
     * Sends the same message body to multiple recipients in a single API call.
     * ReveSMS returns comma-separated message IDs, one per recipient, in the same order.
     *
     * @throws ConnectionException
     */
    public function sendBulk(BulkSmsRequest $request): BulkSmsResponse
    {
        try {
            $response = $this->client()->post('sendtext', [
                'apikey' => $this->credentials['api_key'] ?? '',
                'secretkey' => $this->credentials['secret_key'] ?? '',
                'callerID' => $request->from ?? '',
                'toUser' => implode(',', array_map(fn (string $p): string => ltrim($p, '+'), $request->recipients)),
                'messageContent' => $request->body,
            ]);

            if ($response->failed() || $response->json('Status') !== '0') {
                return new BulkSmsResponse(
                    successful: false,
                    errorMessage: $response->json('Text', 'Unknown error'),
                    raw: $response->json() ?? [],
                );
            }

            $data = $response->json() ?? [];
            $rawIds = (string) ($data['Message_ID'] ?? '');

            // ReveSMS returns comma-separated IDs in the same order as recipients.
            $perRecipientIds = $rawIds !== ''
                ? array_map('trim', explode(',', $rawIds))
                : [];

            return new BulkSmsResponse(
                successful: true,
                perRecipientIds: $perRecipientIds,
                raw: $data,
            );
        } catch (ConnectionException $e) {
            return new BulkSmsResponse(
                successful: false,
                errorMessage: 'Connection failed: '.$e->getMessage(),
            );
        }
    }

    public function fetchDeliveryStatus(string $gatewayMessageId): DeliveryStatusResponse
    {
        try {
            $response = $this->client()->get('getstatus', [
                'apikey' => $this->credentials['api_key'] ?? '',
                'secretkey' => $this->credentials['secret_key'] ?? '',
                'messageid' => $gatewayMessageId,
            ]);

            $data = $response->json() ?? [];
            $status = (string) ($data['Status'] ?? '');
            $text = strtoupper((string) ($data['Text'] ?? ''));

            if ($response->failed()) {
                return new DeliveryStatusResponse(
                    outcome: DeliveryOutcome::InTransit,
                    errorMessage: $text !== '' ? $text : 'HTTP error',
                    raw: $data,
                );
            }

            // Status "0" = DELIVRD, "4" = SENT (accepted, in transit), anything else = failed.
            return match ($status) {
                '4',
                '0' => new DeliveryStatusResponse(
                    outcome: DeliveryOutcome::Delivered,
                    rawStatus: $text,
                    deliveredAt: $this->parseDeliveryTime((string) ($data['Delivery Time'] ?? '')),
                    raw: $data,
                ),
// The API did not update the status after i receive the message, it is still showing 4,
// but the message is delivered and i receive the delivery report with status 4,
// so I assume that status 4 means in transit or accepted by carrier, and status 0 means delivered.
// I will treat both as delivered for now until I can confirm otherwise with the provider.
//                '4' => new DeliveryStatusResponse(
//                    outcome: DeliveryOutcome::InTransit,
//                    rawStatus: $text,
//                    raw: $data,
//                ),
                default => new DeliveryStatusResponse(
                    outcome: DeliveryOutcome::Failed,
                    rawStatus: $text,
                    errorMessage: $text,
                    raw: $data,
                ),
            };
        } catch (ConnectionException $e) {
            return new DeliveryStatusResponse(
                outcome: DeliveryOutcome::InTransit,
                errorMessage: 'Connection failed: '.$e->getMessage(),
            );
        }
    }

    /**
     * Parses the ReveSMS "Delivery Time" field which is a Unix epoch in milliseconds.
     */
    private function parseDeliveryTime(string $epochMillis): ?Carbon
    {
        if ($epochMillis === '' || $epochMillis === '0') {
            return null;
        }

        try {
            return Carbon::createFromTimestampMs((int) $epochMillis);
        } catch (\Throwable) {
            return null;
        }
    }

    private function client(): PendingRequest
    {
        $baseUrl = rtrim((string) ($this->credentials['base_url'] ?? ''), '/');

        return Http::baseUrl($baseUrl)
            ->withoutVerifying()
            ->asJson()
            ->acceptJson();
    }
}
