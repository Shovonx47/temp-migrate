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

final readonly class QSmsDriver implements SmsGateway, SupportsBulkSms, SupportsDeliveryStatus
{
    public function __construct(private array $credentials = []) {}

    private function client(): PendingRequest
    {
        $baseUrl = rtrim((string) ($this->credentials['base_url'] ?? ''), '/');

        return Http::baseUrl($baseUrl)
            ->withoutVerifying()
            ->asJson()
            ->acceptJson();
    }

    public function send(SmsRequest $request): SmsResponse
    {
        try {
            $response = $this->client()->post('api/v2/SendSMS', [
                'ApiKey' => $this->credentials['api_key'] ?? '',
                'ClientId' => $this->credentials['client_id'] ?? '',
                'SenderId' => $request->from ?? '',
                'MobileNumbers' => $request->to,
                'Message' => $request->body,
            ]);

            $data = $response->json() ?? [];

            if ($response->failed() || ($data['ErrorCode'] ?? -1) != 0) {
                return new SmsResponse(
                    successful: false,
                    errorMessage: $data['ErrorDescription'] ?? 'Unknown error',
                    raw: $data,
                );
            }

            // QSMS returns Data as an array of {MobileNumber, MessageId} objects.
            $messageId = (string) ($data['Data'][0]['MessageId'] ?? '');

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
            $response = $this->client()->post('api/v2/SendSMS', [
                'ApiKey' => $this->credentials['api_key'] ?? '',
                'ClientId' => $this->credentials['client_id'] ?? '',
                'SenderId' => $request->from ?? '',
                'MobileNumbers' => implode(',', $request->recipients),
                'Message' => $request->body,
            ]);

            $data = $response->json() ?? [];

            if ($response->failed() || ($data['ErrorCode'] ?? -1) != 0) {
                return new BulkSmsResponse(
                    successful: false,
                    errorMessage: $data['ErrorDescription'] ?? 'Unknown error',
                    raw: $data,
                );
            }

            // QSMS returns Data as [{MobileNumber, MessageId}, …] — build a phone→ID map
            // so we can re-index by the original recipient order.
            $byPhone = collect($data['Data'] ?? [])->keyBy('MobileNumber');

            $perRecipientIds = array_map(
                fn (string $phone): ?string => $byPhone->get($phone)['MessageId'] ?? null,
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
            $response = $this->client()->get('api/v2/MessageStatus', [
                'ApiKey' => $this->credentials['api_key'] ?? '',
                'ClientId' => $this->credentials['client_id'] ?? '',
                'MessageId' => $gatewayMessageId,
            ]);

            $data = $response->json() ?? [];

            if ($response->failed() || ($data['ErrorCode'] ?? -1) != 0) {
                return new DeliveryStatusResponse(
                    outcome: DeliveryOutcome::InTransit,
                    errorMessage: $data['ErrorDescription'] ?? 'Unknown error',
                    raw: $data,
                );
            }

            $status = strtoupper((string) ($data['Data']['Status'] ?? ''));
            $rawDoneDate = (string) ($data['Data']['DoneDate'] ?? '');

            return match (true) {
                $status === 'DELIVRD' => new DeliveryStatusResponse(
                    outcome: DeliveryOutcome::Delivered,
                    rawStatus: $status,
                    deliveredAt: $rawDoneDate !== '' ? $this->parseDoneDate($rawDoneDate) : null,
                    raw: $data,
                ),
                in_array($status, ['SENT', 'ACCEPTD', ''], strict: true) => new DeliveryStatusResponse(
                    outcome: DeliveryOutcome::InTransit,
                    rawStatus: $status !== '' ? $status : null,
                    raw: $data,
                ),
                default => new DeliveryStatusResponse(
                    outcome: DeliveryOutcome::Failed,
                    rawStatus: $status,
                    errorMessage: $status,
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
     * Parses the QSMS DoneDate format, e.g. "16-Mar-2018 05:11:00 PM".
     */
    private function parseDoneDate(string $doneDate): ?Carbon
    {
        try {
            return Carbon::createFromFormat('d-M-Y h:i:s A', $doneDate);
        } catch (\Throwable) {
            return null;
        }
    }
}
