<?php

namespace App\Services\Payment\Data;

readonly class PaymentResponse
{
    public function __construct(
        public bool $successful,
        public string $gatewayDriver,
        public string $gatewayStatus,
        public ?string $gatewayPaymentId = null,
        public ?string $checkoutUrl = null,
        public ?int $amount = null,
        public ?string $currency = 'BDT',
        public array $raw = [],
        public ?string $message = null,
    ) {}
}
