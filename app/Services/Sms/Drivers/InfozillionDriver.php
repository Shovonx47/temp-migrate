<?php

namespace App\Services\Sms\Drivers;

use App\Contracts\SmsGateway;
use App\Contracts\SupportsBulkSms;
use App\Contracts\SupportsDashboard;
use App\Contracts\SupportsDeliveryStatus;
use App\Enums\DeliveryOutcome;
use App\Services\Sms\Data\BulkSmsRequest;
use App\Services\Sms\Data\BulkSmsResponse;
use App\Services\Sms\Data\DashboardData;
use App\Services\Sms\Data\DeliveryStatusResponse;
use App\Services\Sms\Data\SmsRequest;
use App\Services\Sms\Data\SmsResponse;
use App\Services\Sms\Data\StatCard;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final readonly class InfozillionDriver implements SmsGateway, SupportsBulkSms, SupportsDashboard, SupportsDeliveryStatus
{
    private const string A2P_SUCCESS_CODE = '9000';

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

            if ($response->failed() || ((string) ($data['serverResponseCode'] ?? '')) !== self::A2P_SUCCESS_CODE) {
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

            if ($response->failed() || ((string) ($data['serverResponseCode'] ?? '')) !== self::A2P_SUCCESS_CODE) {
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

    public function fetchDeliveryStatus(string $gatewayMessageId, ?string $recipient = null): DeliveryStatusResponse
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
                    'msisdnList' => $recipient !== null ? [ltrim($recipient, '+')] : [],
                    'serverReference' => $gatewayMessageId,
                ],
            );

            $data = $response->json() ?? [];

            if ($response->failed() || ((string) ($data['serverResponseCode'] ?? '')) !== self::A2P_SUCCESS_CODE) {
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

    public function getDashboardData(): DashboardData
    {
        $type = strtolower(trim((string) ($this->credentials['api_type'] ?? 'mno')));

        $carrierCodes = $type === 'iptsp'
            ? [
                'ADN' => 'ADN',
                'MN' => 'Mirnet',
                'BN' => 'Bracnet',
                'AIT' => 'AmberIT',
                'RT' => 'RanksITT',
                'PRM' => 'Premium',
                'FN' => 'FusionNet',
                'BR' => 'Brilliant',
                'MTN' => 'Metronet',
                'RCO' => 'RaceOnline',
                'BPL' => 'BanglaPhone',
                'ICN' => 'Icon',
                'WEB' => 'Weblink',
                'BDC' => 'BDCOM',
                'RD' => 'RedData',
                'L3' => 'Link3',
                'BTCL' => 'BTCL',
                'AGNI' => 'AGNI',
                'ICC' => 'ICC',
                'RG' => 'RoyalGreen',
            ]
            : [
                'GP' => 'GrameenPhone',
                'RB' => 'Robi',
                'TT' => 'Teletalk',
                'BL' => 'Banglalink',
            ];

        $enabledCarriers = $this->credentials['enabled_carriers'] ?? [];

        if (! empty($enabledCarriers) && is_array($enabledCarriers)) {
            $carrierCodes = array_filter($carrierCodes, fn ($name, $code) => in_array($code, $enabledCarriers), ARRAY_FILTER_USE_BOTH);
        }

        $wallet = $this->fetchBalance('check-current-balance', [
            'apiKey' => $this->credentials['api_key'] ?? '',
        ]);

        $cards = [new StatCard(
            label: 'Wallet Balance',
            value: $wallet ?? 'N/A',
            description: 'Available balance at CP end',
            color: $wallet !== null ? 'success' : 'danger',
            icon: 'heroicon-o-banknotes',
        )];

        foreach ($carrierCodes as $code => $name) {
            $balance = $this->fetchBalance('check-credit-balance', [
                'username' => $this->credentials['username'] ?? '',
                'password' => $this->credentials['password'] ?? '',
                'mno' => $code,
                'apiKey' => $this->credentials['api_key'] ?? '',
            ]);

            $cards[] = new StatCard(
                label: "{$name} Balance",
                value: $balance ?? 'N/A',
                description: "{$code} carrier credit",
                icon: 'heroicon-o-device-phone-mobile',
            );
        }

        return new DashboardData(cards: $cards);
    }

    private function fetchBalance(string $endpoint, array $payload): ?string
    {
        try {
            $response = $this->client()->post($this->resolveEndpoint($endpoint), $payload);
            $data = $response->json() ?? [];

            if ($response->failed() || ((string) ($data['serverResponseCode'] ?? '')) !== self::A2P_SUCCESS_CODE) {
                return null;
            }

            return $data['availableBalance'] ?? null;
        } catch (ConnectionException) {
            return null;
        }
    }

    private function buildSendPayload(array $recipients, string $body, string $senderId, bool $isUnicode): array
    {
        $recipientCount = count($recipients);
        $configuredType = $this->credentials['transaction_type'] ?? 'T';
        $transactionType = $recipientCount > 1 ? 'P' : $configuredType;

        $messageType = $isUnicode
            ? '3'
            : ($this->credentials['message_type'] ?? '1');

        $isLongSms = (bool) ($this->credentials['is_long_sms'] ?? (mb_strlen($body) > 160));

        $payload = [
            'username' => $this->credentials['username'] ?? '',
            'password' => $this->credentials['password'] ?? '',
            'billMsisdn' => $this->credentials['bill_msisdn'] ?? '',
            'apiKey' => $this->credentials['api_key'] ?? '',
            'cli' => $senderId,
            'msisdnList' => array_map(fn (string $msisdn) => ltrim($msisdn, '+'), $recipients),
            'transactionType' => $transactionType,
            'messageType' => (string) $messageType,
            'isLongSMS' => $isLongSms,
            'message' => $body,
        ];

        $campaignId = $this->credentials['campaign_id'] ?? null;
        if ($transactionType === 'P') {
            $payload['campaignId'] = ! empty($campaignId) ? $campaignId : 'camp-'.date('YmdHis');
        } elseif (! empty($campaignId)) {
            $payload['campaignId'] = $campaignId;
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
        // Check for DND filtered numbers
        $dndList = $data['dndMsisdn'] ?? [];
        if (! empty($dndList) && is_array($dndList)) {
            return new DeliveryStatusResponse(
                outcome: DeliveryOutcome::Failed,
                errorMessage: 'Recipient filtered due to DND enlistment: '.implode(', ', $dndList),
                raw: $data,
            );
        }

        // Check for Invalid MSISDNs filtered by MNPSP
        $invalidList = $data['invalidMsisdn'] ?? [];
        if (! empty($invalidList) && is_array($invalidList)) {
            return new DeliveryStatusResponse(
                outcome: DeliveryOutcome::Failed,
                errorMessage: 'Invalid MSISDN prefix or format: '.implode(', ', $invalidList),
                raw: $data,
            );
        }

        $deliveryStatuses = $data['deliveryStatus'] ?? [];

        if (empty($deliveryStatuses)) {
            $a2pStatus = $data['a2pDeliveryStatus'] ?? '';

            if (str_contains((string) $a2pStatus, 'failure')) {
                return new DeliveryStatusResponse(
                    outcome: DeliveryOutcome::Failed,
                    rawStatus: (string) $a2pStatus,
                    errorMessage: (string) ($data['serverResponseMessage'] ?? $a2pStatus),
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
                errorMessage: 'SMS delivery status: '.$firstStatus,
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
            'check-credit-balance' => $type === 'iptsp'
                ? 'a2p-proxy-api-iptsp/api/v1/check-credit-balance'
                : 'a2p-proxy-api/api/v1/check-credit-balance',
            'check-current-balance' => 'a2p-wallet/api/v1/check-current-balance',
            default => $action,
        };
    }

    private function client(): PendingRequest
    {
        $baseUrl = rtrim((string) ($this->credentials['base_url'] ?? ''), '/');

        return Http::baseUrl($baseUrl)
            ->withoutVerifying()
            ->asJson()
            ->acceptJson()
            ->timeout(10);
    }
}
