<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\SignupRegisterGateway;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SignupRegisterGatewaySender
{
    public function send(SignupRegisterGateway $gateway, string $phone, string $otp, int $expiryMinutes): void
    {
        $payload = [
            'sender_id' => $gateway->sender_id,
            'numbers' => $this->formatPhone($phone, $gateway->phone_format),
            'body' => $this->renderBody($gateway->body_template, $otp, $expiryMinutes),
        ];

        $timeout = max(1, (int) $gateway->timeout_seconds);

        $request = Http::acceptJson()
            ->timeout($timeout)
            ->connectTimeout(5)
            ->retry([100, 500], throw: false)
            ->withToken((string) $gateway->bearer_token);

        $method = strtoupper((string) $gateway->http_method);
        $url = (string) $gateway->endpoint_url;

        $response = $method === 'GET'
            ? $request->get($url, $payload)
            : $request->asJson()->post($url, $payload);

        $this->ensureAccepted($response, $gateway);
    }

    protected function renderBody(string $template, string $otp, int $expiryMinutes): string
    {
        return str_replace(
            ['{otp}', '{minutes}'],
            [$otp, (string) $expiryMinutes],
            $template,
        );
    }

    protected function formatPhone(string $phone, string $format): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($format === 'e164') {
            return str_starts_with($phone, '+') ? $phone : '+'.$digits;
        }

        if (str_starts_with($digits, '880') && strlen($digits) === 13) {
            return substr($digits, 2);
        }

        return $digits;
    }

    protected function ensureAccepted(Response $response, SignupRegisterGateway $gateway): void
    {
        if ($response->successful()) {
            return;
        }

        $message = $response->json('message') ?? $response->body();

        throw new RuntimeException(sprintf(
            'Signup register gateway [%s] returned HTTP %s: %s',
            $gateway->name,
            $response->status(),
            is_string($message) ? $message : json_encode($message),
        ));
    }
}
