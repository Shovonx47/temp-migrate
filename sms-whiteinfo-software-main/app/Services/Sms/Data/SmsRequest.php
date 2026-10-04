<?php

namespace App\Services\Sms\Data;

readonly class SmsRequest
{
    public function __construct(
        public string $to,
        public string $body,
        public ?string $from = null,
        public bool $isUnicode = false,
    ) {}
}
