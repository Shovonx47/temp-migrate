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
use Illuminate\Support\Facades\Http;

final readonly class InfozillionDriver implements SmsGateway, SupportsBulkSms, SupportsDeliveryStatus
{
    private const A2P_SUCCESS_CODE = '9000';

    public function __construct(private array $credentials = []) {}

    public function send(SmsRequest $request): SmsResponse
    {
        try {
            $response = $this->client()->post(
                $this->resolveEndpoint('send-sms'),
                $this->buildSendPayload(
                    recipients: [$request->to],
                    body: $request->body,
                    senderId: $request->from ?? '',
                    isUnicode: $request->isUnicode,
                ),
            );

            $data = $response->json() ?? [];

            if ($response->failed() || ($data['serverResponseCode'] ?? '') !== self::A2P_SUCCESS_CODE) {
                return new SmsResponse(
                    successful: false,
                    errorMessage: $data['serverResponseMessage'] ?? 'Unknown error',
                    raw: $data,
                );
            }

            return new SmsResponse(
                successful: true,
                gatewayMessageId: $data['serverTxnId'] ?? null,
                raw: $data,
            );
        } catch (ConnectionException $e) {
            return new SmsResponse(
                successful: false,
                errorMessage: 'Connection failed: '.$e->getMessage(),
            );
        }
    }

    public function sendBulk(BulkSmsRequest $request): BulkSmsResponse
    {
        try {
            $response = $this->client()->post(
                $this->resolveEndpoint('send-sms'),
                $this->buildSendPayload(
                    recipients: $request->recipients,
                    body: $request->body,
                    senderId: $request->from ?? '',
                    isUnicode: $request->isUnicode,
                ),
            );

            $data = $response->json() ?? [];

            if ($response->failed() || ($data['serverResponseCode'] ?? '') !== self::A2P_SUCCESS_CODE) {
                return new BulkSmsResponse(
                    successful: false,
                    errorMessage: $data['serverResponseMessage'] ?? 'Unknown error',
                    raw: $data,
                );
            }

            return new BulkSmsResponse(
                successful: true,
                gatewayBatchId: $data['serverTxnId'] ?? null,
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
            $response = $this->client()->post(
                $this->resolveEndpoint('check-delivery-report'),
                [
                    'username' => $this->credentials['username'] ?? '',
                    'password' => $this->credentials['password'] ?? '',
                    'billMsisdn' => $this->credentials['bill_msisdn'] ?? '',
                    'usernameSecondary' => $this->credentials['username_secondary'] ?? '',
                    'passwordSecondary' => $this->credentials['password_secondary'] ?? '',
                    'billMsisdnSecondary' => $this->credentials['bill_msisdn_secondary'] ?? '',
                    'apiKey' => $this->credentials['api_key'] ?? '',
                    'msisdnList' => [],
                    'serverReference' => $gatewayMessageId,
                ],
            );

            $data = $response->json() ?? [];

            if ($response->failed() || ($data['serverResponseCode'] ?? '') !== self::A2P_SUCCESS_CODE) {
                return new DeliveryStatusResponse(
                    outcome: DeliveryOutcome::InTransit,
                    errorMessage: $data['serverResponseMessage'] ?? 'Delivery check failed',
                    raw: $data,
                );
            }

            return $this->mapDeliveryStatus($data);
        } catch (ConnectionException $e) {
            return new DeliveryStatusResponse(
                outcome: DeliveryOutcome::InTransit,
                errorMessage: 'Connection failed: '.$e->getMessage(),
            );
        }
    }

    private function buildSendPayload(array $recipients, string $body, string $senderId, bool $isUnicode): array
    {
        $payload = [
            'username' => $this->credentials['username'] ?? '',
            'password' => $this->credentials['password'] ?? '',
            'billMsisdn' => $this->credentials['bill_msisdn'] ?? '',
            'apiKey' => $this->credentials['api_key'] ?? '',
            'cli' => $senderId,
            'msisdnList' => $recipients,
            'transactionType' => $this->credentials['transaction_type'] ?? 'T',
            'messageType' => $isUnicode ? '3' : ($this->credentials['message_type'] ?? '1'),
            'isLongSMS' => (bool) ($this->credentials['is_long_sms'] ?? false),
            'message' => $body,
        ];

        if (! empty($this->credentials['campaign_id'])) {
            $payload['campaignId'] = $this->credentials['campaign_id'];
        }

        if (! empty($this->credentials['username_secondary'])) {
            $payload['usernameSecondary'] = $this->credentials['username_secondary'];
            $payload['passwordSecondary'] = $this->credentials['password_secondary'] ?? '';
            $payload['billMsisdnSecondary'] = $this->credentials['bill_msisdn_secondary'] ?? '';
        }

        return $payload;
    }

    private function mapDeliveryStatus(array $data): DeliveryStatusResponse
    {
        $deliveryStatuses = $data['deliveryStatus'] ?? [];

        if (empty($deliveryStatuses)) {
            $a2pStatus = $data['a2pDeliveryStatus'] ?? '';

            if (str_contains((string) $a2pStatus, 'failure')) {
                return new DeliveryStatusResponse(
                    outcome: DeliveryOutcome::Failed,
                    rawStatus: (string) $a2pStatus,
                    raw: $data,
                );
            }

            return new DeliveryStatusResponse(
                outcome: DeliveryOutcome::InTransit,
                rawStatus: (string) $a2pStatus,
                raw: $data,
            );
        }

        $firstStatus = (string) ($deliveryStatuses[0] ?? '');
        $parts = explode('-', $firstStatus, 2);
        $status = strtolower(trim((string) ($parts[1] ?? $firstStatus)));

        return match ($status) {
            'delivered' => new DeliveryStatusResponse(
                outcome: DeliveryOutcome::Delivered,
                rawStatus: $firstStatus,
                raw: $data,
            ),
            'undelivered', 'failed' => new DeliveryStatusResponse(
                outcome: DeliveryOutcome::Failed,
                rawStatus: $firstStatus,
                raw: $data,
            ),
            default => new DeliveryStatusResponse(
                outcome: DeliveryOutcome::InTransit,
                rawStatus: $firstStatus,
                raw: $data,
            ),
        };
    }

    private function resolveEndpoint(string $action): string
    {
        $type = $this->credentials['api_type'] ?? 'mno';

        return match ($action) {
            'send-sms' => $type === 'iptsp'
                ? 'a2p-sms-iptsp/api/v1/send-sms'
                : 'a2p-sms/api/v1/send-sms',
            'check-delivery-report' => $type === 'iptsp'
                ? 'a2p-proxy-api-iptsp/api/v1/check-delivery-report'
                : 'a2p-proxy-api/api/v1/check-delivery-report',
            default => $action,
        };
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
