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

            $data = $response->json() ?? [];

            if ($response->failed() || ($data['status'] ?? '') !== 'success') {
                return new SmsResponse(
                    successful: false,
                    errorMessage: $data['message'] ?? $this->extractErrorMessage($data),
                    raw: $data,
                );
            }

            $messageId = (string) ($data['messageids'][0]['messageid'] ?? '');

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

            $data = $response->json() ?? [];

            if ($response->failed() || ($data['status'] ?? '') !== 'success') {
                return new BulkSmsResponse(
                    successful: false,
                    errorMessage: $data['message'] ?? $this->extractErrorMessage($data),
                    raw: $data,
                );
            }

            // Anbernet returns per-recipient message IDs as [{receiver, messageid}, ...].
            // Build a phone→ID map and resolve in the same order as recipients.
            $byReceiver = collect($data['messageids'] ?? [])->keyBy(
                fn (array $item): string => (string) ($item['receiver'] ?? ''),
            );

            $perRecipientIds = array_map(
                fn (string $phone): ?string => $byReceiver->get((string) $phone, [])['messageid'] ?? null,
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

    public function fetchDeliveryStatus(string $gatewayMessageId): DeliveryStatusResponse
    {
        try {
            $response = $this->client()->post('api/v1/dlrcheck', [
                'account' => $this->credentials['account'] ?? '',
                'password' => $this->credentials['password'] ?? '',
                'token' => $this->credentials['token'] ?? '',
                'messageid' => $gatewayMessageId,
            ]);

            $data = $response->json() ?? [];

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
        return [
            'account' => $this->credentials['account'] ?? '',
            'password' => $this->credentials['password'] ?? '',
            'token' => $this->credentials['token'] ?? '',
            'senderid' => $senderId,
            'receivers' => $recipients,
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
            isset($data['Balance Status']) => 'Balance check response instead of SMS send',
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

    private function client(): PendingRequest
    {
        $baseUrl = rtrim((string) ($this->credentials['base_url'] ?? ''), '/');

        return Http::baseUrl($baseUrl)
            ->withoutVerifying()
            ->asJson()
            ->acceptJson();
    }
}
