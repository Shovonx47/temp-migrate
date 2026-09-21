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

final readonly class AnbernetDriver implements SmsGateway, SupportsBulkSms, SupportsDeliveryStatus
{
    public function __construct(private array $credentials = []) {}

    public function send(SmsRequest $request): SmsResponse
    {
        try {
            $response = $this->client()->post('api/v1/sendsms', $this->buildSendPayload(
                recipients: [$request->to],
                body: $request->body,
                senderId: $request->from ?? '',
            ));

            $data = $this->unwrapResponseData($response->json());
            $status = strtolower((string) ($data['status'] ?? $data['Status'] ?? ''));
            $hasMessageIds = ! empty($data['messageids']) || ! empty($data['message_ids']) || ! empty($data['messageid']);
            $isSuccessful = $response->successful() && ($status === 'success' || ($status === '' && $hasMessageIds));

            if (! $isSuccessful) {
                return new SmsResponse(
                    successful: false,
                    errorMessage: $this->extractErrorMessage($data),
                    raw: $data,
                );
            }

            $messageId = (string) (
                $data['messageids'][0]['messageid']
                ?? $data['messageids'][0]['messageId']
                ?? $data['messageids'][0]['id']
                ?? $data['messageid']
                ?? $data['messageId']
                ?? ''
            );

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

    public function sendBulk(BulkSmsRequest $request): BulkSmsResponse
    {
        try {
            $response = $this->client()->post('api/v1/sendsms', $this->buildSendPayload(
                recipients: $request->recipients,
                body: $request->body,
                senderId: $request->from ?? '',
            ));

            $data = $this->unwrapResponseData($response->json());
            $status = strtolower((string) ($data['status'] ?? $data['Status'] ?? ''));
            $hasMessageIds = ! empty($data['messageids']) || ! empty($data['message_ids']);
            $isSuccessful = $response->successful() && ($status === 'success' || ($status === '' && $hasMessageIds));

            if (! $isSuccessful) {
                return new BulkSmsResponse(
                    successful: false,
                    errorMessage: $this->extractErrorMessage($data),
                    raw: $data,
                );
            }

            // Anbernet returns per-recipient message IDs as [{receiver, messageid}, ...].
            // Build a phone→ID map and resolve in the same order as recipients.
            $rawMessageIds = $data['messageids'] ?? $data['message_ids'] ?? [];
            $byReceiver = collect($rawMessageIds)->keyBy(
                fn (array $item): string => ltrim((string) ($item['receiver'] ?? $item['to'] ?? $item['mobile'] ?? ''), '+'),
            );

            $perRecipientIds = array_map(
                fn (string $phone): ?string => $byReceiver->get(ltrim(trim((string) $phone), '+'), [])['messageid']
                    ?? $byReceiver->get(ltrim(trim((string) $phone), '+'), [])['messageId']
                    ?? $byReceiver->get(ltrim(trim((string) $phone), '+'), [])['id']
                    ?? null,
                $request->recipients,
            );

            return new BulkSmsResponse(
                successful: true,
                perRecipientIds: array_values($perRecipientIds),
                raw: $data,
            );
        } catch (ConnectionException $e) {
            return new BulkSmsResponse(
                successful: false,
                errorMessage: 'Connection failed: '.$e->getMessage(),
            );
        }
    }

    public function fetchDeliveryStatus(string $gatewayMessageId, ?string $recipient = null): DeliveryStatusResponse
    {
        try {
            $response = $this->client()->post('api/v1/dlrcheck', [
                'account' => $this->credentials['account'] ?? '',
                'password' => $this->credentials['password'] ?? '',
                'token' => $this->credentials['token'] ?? '',
                'messageid' => $gatewayMessageId,
            ]);

            $data = $this->unwrapResponseData($response->json());

            if ($response->failed()) {
                return new DeliveryStatusResponse(
                    outcome: DeliveryOutcome::InTransit,
                    errorMessage: 'HTTP error',
                    raw: $data,
                );
            }

            $status = strtoupper((string) ($data['status'] ?? ''));

            return match ($status) {
                'DELIVERED' => new DeliveryStatusResponse(
                    outcome: DeliveryOutcome::Delivered,
                    rawStatus: $status,
                    deliveredAt: $this->parseDateTime((string) ($data['datetime'] ?? '')),
                    raw: $data,
                ),
                'ACCEPTD',
                'PENDING' => new DeliveryStatusResponse(
                    outcome: DeliveryOutcome::InTransit,
                    rawStatus: $status,
                    raw: $data,
                ),
                default => new DeliveryStatusResponse(
                    outcome: DeliveryOutcome::Failed,
                    rawStatus: $status,
                    errorMessage: $status !== '' ? $status : 'Unknown status',
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

    private function buildSendPayload(array $recipients, string $body, string $senderId): array
    {
        $normalizedRecipients = array_values(array_map(
            fn (string $phone): string => ltrim(trim($phone), '+'),
            $recipients,
        ));

        return [
            'account' => $this->credentials['account'] ?? '',
            'password' => $this->credentials['password'] ?? '',
            'token' => $this->credentials['token'] ?? '',
            'senderid' => $senderId,
            'receivers' => $normalizedRecipients,
            'msgdata' => $body,
            'flashon' => 0,
            'transtype' => 'T',
            'campaignId' => '',
        ];
    }

    private function extractErrorMessage(array $data): string
    {
        return match (true) {
            isset($data['message']) => (string) $data['message'],
            isset($data['Message']) => (string) $data['Message'],
            isset($data['error']) => (string) $data['error'],
            isset($data['Error']) => (string) $data['Error'],
            isset($data['description']) => (string) $data['description'],
            isset($data['Balance Status']) => 'Balance check response instead of SMS send',
            ! empty($data) => json_encode($data),
            default => 'Unknown error',
        };
    }

    private function parseDateTime(string $datetime): ?Carbon
    {
        if ($datetime === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $datetime);
        } catch (\Throwable) {
            return null;
        }
    }

    private function unwrapResponseData(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        // Handle array-wrapped responses like [{"status":"success",...}, 200] or [{...}]
        if (isset($data[0]) && is_array($data[0])) {
            return $data[0];
        }

        return $data;
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
