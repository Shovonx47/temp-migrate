<?php

namespace App\Services\Billing\DTO;

use App\Enums\ProfitStatus;

readonly class ProfitResult
{
    public function __construct(
        public ProfitStatus $status,
        public RevenueResult $revenue,
        public CostResolutionResult $cost,
        public ?float $profit,
        public ?float $margin,
        public ?string $incompleteReason,
    ) {}

    public static function incomplete(
        RevenueResult $revenue,
        CostResolutionResult $cost,
    ): self {
        return new self(
            status: ProfitStatus::Incomplete,
            revenue: $revenue,
            cost: $cost,
            profit: null,
            margin: null,
            incompleteReason: $cost->incompleteReason,
        );
    }
}
