<?php

namespace App\Services\Sms\Data;

readonly class BulkSmsRequest
{
    /**
     * @param  string[]  $recipients  E.164 formatted phone numbers, order matters
     */
    public function __construct(
        public array $recipients,
        public string $body,
        public ?string $from = null,
        public bool $isUnicode = false,
    ) {}
}
