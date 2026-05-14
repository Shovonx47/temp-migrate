<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use InvalidArgumentException;

class PaymentGatewayResolver
{
    public function resolve(string $gateway): PaymentGateway
    {
        return match ($gateway) {
            'piprapay' => new PipraPayDriver(
                apiKey: config('services.piprapay.api_key'),
                baseUrl: config('services.piprapay.sandbox')
                    ? config('services.piprapay.sandbox_url')
                    : config('services.piprapay.live_url'),
            ),
            default => throw new InvalidArgumentException("Unsupported payment gateway: {$gateway}"),
        };
    }
}
