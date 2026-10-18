<?php

namespace App\Services\Billing\DTO;

readonly class DlrMetrics
{
    public function __construct(
        public int $delivered,
        public int $dlrPending,
        public int $sendFailed,
        public int $dlrFailed,
        public ?float $deliveryPercent,
    ) {}
}
