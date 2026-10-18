<?php

namespace App\Services\Billing\DTO;

readonly class RevenueResult
{
    public function __construct(
        public float $total,
        public int $messageCount,
        public int $parts,
    ) {}
}
