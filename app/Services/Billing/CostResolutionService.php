<?php

namespace App\Services\Billing;

use App\Enums\CostResolutionStatus;
use App\Enums\MessageStatus;
use App\Models\Gateway;
use App\Models\GatewayOperatorCostPeriod;
use App\Models\GatewayThirdPartyDippingPeriod;
use App\Services\Billing\DTO\CostResolutionResult;
use App\Services\Billing\DTO\DlrMetrics;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CostResolutionService
{
    public const string MISSING_MANDATORY_DIPPING = 'Missing Mandatory Dipping Cost';

    public function resolveForGateway(Gateway $gateway, Carbon $from, Carbon $to): CostResolutionResult
    {
        if ($gateway->dipping_required && ! DippingChargeResolver::gatewayHasConfiguration($gateway, $to)) {
            $messageCount = $this->countBillableMessages($gateway, $from, $to);

            return CostResolutionResult::incomplete(
                reason: self::MISSING_MANDATORY_DIPPING,
                messageCount: $messageCount,
                dlrMetrics: $this->resolveDlrMetrics($gateway, $from, $to),
            );
        }

        $aggregates = $this->aggregateMessageCosts($gateway, $from, $to);

        $operatorCostPeriod = GatewayOperatorCostPeriod::findForPeriod($gateway, $from, $to);
        $operatorCost = $operatorCostPeriod !== null
            ? (float) $operatorCostPeriod->manual_cost
            : (float) $aggregates->operator_cost;

        $primaryDippingCost = (float) $aggregates->primary_dipping_cost;

        $thirdPartyDippingCost = $this->resolveThirdPartyDippingCost(
            $gateway,
            $from,
            $to,
            (float) $aggregates->third_party_dipping_cost,
        );

        $totalCost = round($operatorCost + $primaryDippingCost + ($thirdPartyDippingCost ?? 0.0), 4);

        return new CostResolutionResult(
            operatorCost: round($operatorCost, 4),
            primaryDippingCost: round($primaryDippingCost, 4),
            thirdPartyDippingCost: $thirdPartyDippingCost !== null ? round($thirdPartyDippingCost, 4) : null,
            totalCost: $totalCost,
            status: CostResolutionStatus::Complete,
            incompleteReason: null,
            messageCount: (int) $aggregates->message_count,
            dlrMetrics: $this->resolveDlrMetrics($gateway, $from, $to),
        );
    }

    private function aggregateMessageCosts(Gateway $gateway, Carbon $from, Carbon $to): object
    {
        return DB::table('messages')
            ->where('gateway_id', $gateway->id)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->where('status', '!=', MessageStatus::Failed->value)
            ->selectRaw('
                COUNT(*) as message_count,
                COALESCE(SUM(operator_cost), 0) as operator_cost,
                COALESCE(SUM(primary_dipping_cost), 0) as primary_dipping_cost,
                COALESCE(SUM(third_party_dipping_cost), 0) as third_party_dipping_cost
            ')
            ->first();
    }

    private function countBillableMessages(Gateway $gateway, Carbon $from, Carbon $to): int
    {
        return (int) DB::table('messages')
            ->where('gateway_id', $gateway->id)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->where('status', '!=', MessageStatus::Failed->value)
            ->count();
    }

    private function resolveThirdPartyDippingCost(
        Gateway $gateway,
        Carbon $from,
        Carbon $to,
        float $messageAggregate,
    ): ?float {
        if (! $gateway->has_third_party_dipping) {
            return null;
        }

        $period = GatewayThirdPartyDippingPeriod::findForPeriod($gateway, $from, $to);

        if ($period !== null) {
            return (float) $period->manual_cost;
        }

        return $messageAggregate > 0 ? $messageAggregate : 0.0;
    }

    private function resolveDlrMetrics(Gateway $gateway, Carbon $from, Carbon $to): ?DlrMetrics
    {
        if (! $gateway->dlr_support) {
            return null;
        }

        $rows = DB::table('messages')
            ->where('gateway_id', $gateway->id)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw('
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as send_failed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as dlr_failed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as dlr_pending
            ', [
                MessageStatus::Delivered->value,
                MessageStatus::Failed->value,
                MessageStatus::DlrFailed->value,
                MessageStatus::Sent->value,
            ])
            ->first();

        $delivered = (int) ($rows->delivered ?? 0);
        $sendFailed = (int) ($rows->send_failed ?? 0);
        $dlrFailed = (int) ($rows->dlr_failed ?? 0);
        $dlrPending = (int) ($rows->dlr_pending ?? 0);
        $denominator = $delivered + $dlrPending + $dlrFailed;

        return new DlrMetrics(
            delivered: $delivered,
            dlrPending: $dlrPending,
            sendFailed: $sendFailed,
            dlrFailed: $dlrFailed,
            deliveryPercent: $denominator > 0 ? round(($delivered / $denominator) * 100, 1) : null,
        );
    }
}
