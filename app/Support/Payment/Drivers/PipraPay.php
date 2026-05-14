<?php

namespace App\Support\Payment\Drivers;

use App\Support\Payment\Contracts\PaymentGateway;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

readonly class PipraPay implements PaymentGateway
{
    public function __construct(private array $config)
    {
        if (empty($this->config['url'])) {
            throw new \InvalidArgumentException('PipraPay URL is required in the configuration.');
        }

        if (empty($this->config['key'])) {
            throw new \InvalidArgumentException('PipraPay Key is required in the configuration.');
        }
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->config['url'])
            ->timeout(30)
            ->connectTimeout(10)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'MHS-PIPRAPAY-API-KEY' => $this->config['key'],
            ]);
    }
}
