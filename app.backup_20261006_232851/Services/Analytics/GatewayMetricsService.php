<?php

namespace App\Services\Analytics;

use App\Enums\MessageStatus;
use App\Models\Gateway;
use App\Models\GatewayOperatorCostPeriod;
use App\Models\GatewayThirdPartyDippingPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GatewayMetricsService
{
    /** @var array<int, array<string, mixed>> */
    private array $memo = [];

    /**
     * @return array<string, mixed>
     */
    public function getMetrics(Gateway $gateway): array
    {
        if (isset($this->memo[$gateway->id])) {
            return $this->memo[$gateway->id];
        }

        $stats = DB::table('messages')
            ->leftJoin('senders', 'messages.sender_id', '=', 'senders.id')
            ->where('messages.gateway_id', $gateway->id)
            ->selectRaw("
                COUNT(*) as total_messages,
                SUM(CASE WHEN senders.type IN ('alphanumeric', 'shortcode') THEN 1 ELSE 0 END) as masking_sent,
                SUM(CASE WHEN senders.type NOT IN ('alphanumeric', 'shortcode') OR senders.type IS NULL THEN 1 ELSE 0 END) as non_masking_sent,
                
                SUM(CASE WHEN senders.type IN ('alphanumeric', 'shortcode') AND messages.status = ? THEN 1 ELSE 0 END) as masking_delivered,
                SUM(CASE WHEN (senders.type NOT IN ('alphanumeric', 'shortcode') OR senders.type IS NULL) AND messages.status = ? THEN 1 ELSE 0 END) as non_masking_delivered,
                SUM(CASE WHEN messages.status = ? THEN 1 ELSE 0 END) as total_delivered,
                
                SUM(CASE WHEN messages.status = ? THEN 1 ELSE 0 END) as dlr_pending,
                SUM(CASE WHEN messages.status = ? THEN 1 ELSE 0 END) as send_failed,
                SUM(CASE WHEN messages.status = ? THEN 1 ELSE 0 END) as dlr_failed,
                SUM(CASE WHEN messages.status IN (?, ?) THEN 1 ELSE 0 END) as processing,
                
                SUM(CASE WHEN messages.status != ? THEN messages.cost ELSE 0 END) as revenue,
                SUM(CASE WHEN messages.status != ? THEN messages.operator_cost ELSE 0 END) as operator_cost,
                SUM(CASE WHEN messages.status != ? THEN messages.primary_dipping_cost ELSE 0 END) as primary_dipping_cost,
                SUM(CASE WHEN messages.status != ? THEN messages.third_party_dipping_cost ELSE 0 END) as third_party_dipping_cost
            ", [
                MessageStatus::Delivered->value,
                MessageStatus::Delivered->value,
                MessageStatus::Delivered->value,
                MessageStatus::Sent->value,
                MessageStatus::Failed->value,
                MessageStatus::DlrFailed->value,
                MessageStatus::Pending->value,
                MessageStatus::Sending->value,
                MessageStatus::Failed->value,
                MessageStatus::Failed->value,
                MessageStatus::Failed->value,
                MessageStatus::Failed->value,
            ])
            ->first();

        $totalSent = (int) ($stats->total_messages ?? 0);
        $maskingSent = (int) ($stats->masking_sent ?? 0);
        $nonMaskingSent = (int) ($stats->non_masking_sent ?? 0);

        $maskingDelivered = (int) ($stats->masking_delivered ?? 0);
        $nonMaskingDelivered = (int) ($stats->non_masking_delivered ?? 0);
        $totalDelivered = (int) ($stats->total_delivered ?? 0);

        $dlrPending = (int) ($stats->dlr_pending ?? 0);
        $sendFailed = (int) ($stats->send_failed ?? 0);
        $dlrFailed = (int) ($stats->dlr_failed ?? 0);
        $processing = (int) ($stats->processing ?? 0);

        $hasDlr = (bool) $gateway->dlr_support;

        $dlrPendingPct = ($hasDlr && $totalSent > 0)
            ? round(($dlrPending / $totalSent) * 100, 2)
            : null;

        $sendFailedPct = $totalSent > 0
            ? round(($sendFailed / $totalSent) * 100, 2)
            : 0.0;

        $dlrFailedPct = ($hasDlr && $totalSent > 0)
            ? round(($dlrFailed / $totalSent) * 100, 2)
            : null;

        $processingPct = $totalSent > 0
            ? round(($processing / $totalSent) * 100, 2)
            : 0.0;

        $maskingDeliveryPct = ($hasDlr && $maskingSent > 0)
            ? round(($maskingDelivered / $maskingSent) * 100, 2)
            : ($hasDlr ? 0.0 : null);

        $nonMaskingDeliveryPct = ($hasDlr && $nonMaskingSent > 0)
            ? round(($nonMaskingDelivered / $nonMaskingSent) * 100, 2)
            : ($hasDlr ? 0.0 : null);

        // Rates
        $rates = $gateway->gatewayOperatorRates;
        $maskingCostRate = $rates->isNotEmpty() ? $rates->avg('masking_price') : null;
        $nonMaskingCostRate = $rates->isNotEmpty() ? $rates->avg('non_masking_price') : null;

        // Operator cost
        $costPeriod = GatewayOperatorCostPeriod::where('gateway_id', $gateway->id)
            ->latest('period_end')
            ->first();
        $operatorCost = $costPeriod !== null
            ? (float) $costPeriod->manual_cost
            : (float) ($stats->operator_cost ?? 0);

        // Dipping costs
        $dippingCharge = (float) ($stats->primary_dipping_cost ?? 0);

        $thirdPartyPeriod = $gateway->has_third_party_dipping
            ? GatewayThirdPartyDippingPeriod::where('gateway_id', $gateway->id)->latest('period_end')->first()
            : null;
        $thirdPartyDippingCost = $thirdPartyPeriod !== null
            ? (float) $thirdPartyPeriod->manual_cost
            : ($gateway->has_third_party_dipping ? (float) ($stats->third_party_dipping_cost ?? 0) : 0.0);

        $totalCost = $operatorCost + $dippingCharge + $thirdPartyDippingCost;
        $revenue = (float) ($stats->revenue ?? 0);
        $profit = $revenue - $totalCost;

        // Balances from cached dashboard data if available
        $cachedDashboard = Cache::get("gateway:{$gateway->id}:dashboard");
        $providerBalance = null;
        $dippingBalance = null;

        if (is_array($cachedDashboard)) {
            foreach ($cachedDashboard as $card) {
                $label = strtolower($card['label'] ?? '');
                $val = $card['value'] ?? null;
                if (str_contains($label, 'wallet')) {
                    $dippingBalance = $val;
                } elseif (str_contains($label, 'balance')) {
                    $providerBalance = $val;
                }
            }
        }

        // Verification status
        if (! $hasDlr) {
            $verification = 'Send Only';
            $verificationColor = 'gray';
        } elseif ($dlrPending > 0) {
            $verification = 'Pending DLR';
            $verificationColor = 'warning';
        } else {
            $verification = 'Verified';
            $verificationColor = 'success';
        }

        $result = [
            'masking_sent' => $maskingSent,
            'non_masking_sent' => $nonMaskingSent,
            'total_sent' => $totalSent,

            'masking_delivered' => $hasDlr ? $maskingDelivered : null,
            'non_masking_delivered' => $hasDlr ? $nonMaskingDelivered : null,
            'total_delivered' => $hasDlr ? $totalDelivered : null,

            'dlr_pending' => $hasDlr ? $dlrPending : null,
            'dlr_pending_pct' => $dlrPendingPct,

            'send_failed' => $sendFailed,
            'send_failed_pct' => $sendFailedPct,

            'dlr_failed' => $hasDlr ? $dlrFailed : null,
            'dlr_failed_pct' => $dlrFailedPct,

            'processing' => $processing,
            'processing_pct' => $processingPct,

            'masking_delivery_pct' => $maskingDeliveryPct,
            'non_masking_delivery_pct' => $nonMaskingDeliveryPct,

            'masking_cost' => $maskingCostRate !== null ? (float) $maskingCostRate : 0.0,
            'non_masking_cost' => $nonMaskingCostRate !== null ? (float) $nonMaskingCostRate : 0.0,

            'operator_cost' => $operatorCost,
            'dipping_charge' => $dippingCharge,
            'third_party_dipping_cost' => $thirdPartyDippingCost,
            'total_cost' => $totalCost,

            'provider_balance' => $providerBalance,
            'dipping_balance' => $dippingBalance,

            'revenue' => $revenue,
            'profit' => $profit,

            'verification' => $verification,
            'verification_color' => $verificationColor,
        ];

        $this->memo[$gateway->id] = $result;

        return $result;
    }
}
