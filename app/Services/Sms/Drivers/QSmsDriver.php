<?php

namespace App\Services\Sms\Drivers;

use App\Contracts\SmsGateway;
use App\Contracts\SupportsBulkSms;
use App\Services\Sms\Data\BulkSmsRequest;
use App\Services\Sms\Data\BulkSmsResponse;
use App\Services\Sms\Data\SmsRequest;
use App\Services\Sms\Data\SmsResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final readonly class QSmsDriver implements SmsGateway, SupportsBulkSms
{
    public function __construct(private array $credentials = []) {}


    private function client(): PendingRequest
    {
        $baseUrl = rtrim((string) ($this->credentials['url'] ?? ''), '/');

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

            if ($response->failed() || $response->json("ErrorCode") != 0) {
                return new SmsResponse(
                    successful: false,
                    errorMessage: $response->json('ErrorDescription', 'Unknown error'),
                    raw: $response->json() ?? [],
                );
            }


            return new SmsResponse(successful: true);
        }catch (ConnectionException $e) {
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

            if ($response->failed() || $response->json("ErrorCode") != 0) {
                return new BulkSmsResponse(
                    successful: false,
                    errorMessage: $response->json('ErrorDescription', 'Unknown error'),
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
}
