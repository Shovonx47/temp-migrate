<?php

namespace App\Services\Sms;

class SmsResult
{
    public function __construct(
        public bool $success,
        public ?string $responseId = null,
        public ?string $errorMessage = null,
        public array $raw = [],
    ) {}

    public static function success(?string $responseId = null, array $raw = []): self
    {
        return new self(
            success: true,
            responseId: $responseId,
            raw: $raw,
        );
    }

    public static function failed(?string $errorMessage = null, array $raw = []): self
    {
        return new self(
            success: false,
            responseId: null,
            errorMessage: $errorMessage, raw: $raw,
        );
    }
}
