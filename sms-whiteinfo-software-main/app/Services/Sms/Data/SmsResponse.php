<?php

namespace App\Services\Sms\Data;

readonly class SmsResponse
{
    public function __construct(
        public bool $successful,
        public ?string $gatewayMessageId = null,
        public ?string $errorMessage = null,
        public array $raw = [],
    ) {}
}
