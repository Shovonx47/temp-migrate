<?php

namespace App\Services\Billing;

use App\Enums\CostResolutionStatus;
use App\Enums\ProfitStatus;
use App\Models\Gateway;
use App\Services\Billing\DTO\ProfitResult;
use Illuminate\Support\Carbon;

class GatewayProfitCalculationService
{
    public function __construct(
        private CostResolutionService $costResolutionService,
        private RevenueResolutionService $revenueResolutionService,
    ) {}

    public function calculateForGateway(Gateway $gateway, Carbon $from, Carbon $to): ProfitResult
    {
        $revenue = $this->revenueResolutionService->resolveForPeriod($from, $to, $gateway);
        $cost = $this->costResolutionService->resolveForGateway($gateway, $from, $to);

        if ($cost->status === CostResolutionStatus::Incomplete) {
            return ProfitResult::incomplete($revenue, $cost);
        }

        $profit = round($revenue->total - $cost->totalCost, 4);
        $margin = $revenue->total > 0 ? round(($profit / $revenue->total) * 100, 1) : 0.0;

        return new ProfitResult(
            status: ProfitStatus::Complete,
            revenue: $revenue,
            cost: $cost,
            profit: $profit,
            margin: $margin,
            incompleteReason: null,
        );
    }
}
