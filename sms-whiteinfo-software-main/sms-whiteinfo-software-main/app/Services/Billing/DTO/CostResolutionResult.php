<?php

namespace App\Services\Billing\DTO;

use App\Enums\CostResolutionStatus;

readonly class CostResolutionResult
{
    public function __construct(
        public float $operatorCost,
        public float $primaryDippingCost,
        public ?float $thirdPartyDippingCost,
        public float $totalCost,
        public CostResolutionStatus $status,
        public ?string $incompleteReason,
        public int $messageCount,
        public ?DlrMetrics $dlrMetrics,
    ) {}

    public static function incomplete(
        string $reason,
        float $operatorCost = 0.0,
        float $primaryDippingCost = 0.0,
        ?float $thirdPartyDippingCost = null,
        int $messageCount = 0,
        ?DlrMetrics $dlrMetrics = null,
    ): self {
        return new self(
            operatorCost: $operatorCost,
            primaryDippingCost: $primaryDippingCost,
            thirdPartyDippingCost: $thirdPartyDippingCost,
            totalCost: 0.0,
            status: CostResolutionStatus::Incomplete,
            incompleteReason: $reason,
            messageCount: $messageCount,
            dlrMetrics: $dlrMetrics,
        );
    }
}
