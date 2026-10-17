<?php

namespace App\Services\Sms\Data;

readonly class BulkSmsResponse
{
    /**
     * @param  string[]  $perRecipientIds  Gateway message IDs, indexed in the same
     *                                     order as BulkSmsRequest::$recipients. Empty
     *                                     when the gateway returns a single batch ID
     *                                     for all recipients instead of per-message IDs.
     * @param  array<string, string>  $failedRecipients  Rejected recipients keyed by
     *                                                   normalized E.164 number, with
     *                                                   the gateway rejection reason.
     */
    public function __construct(
        public bool $successful,
        public array $perRecipientIds = [],
        public ?string $gatewayBatchId = null,
        public ?string $errorMessage = null,
        public array $raw = [],
        public array $failedRecipients = [],
    ) {}
}
