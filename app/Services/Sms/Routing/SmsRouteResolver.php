<?php

namespace App\Services\Sms\Routing;

use App\Models\Country;
use App\Models\Gateway;
use App\Models\Operator;
use App\Models\RoutePlan;
use App\Models\RouteRule;
use App\Models\Sender;
use App\Support\MnoCarrier;
use Illuminate\Support\Collection;

class SmsRouteResolver
{
    /**
     * Resolves the ordered chain of Gateways (Priority 1 -> 2 -> 3) for sending a message.
     *
     * @return Collection<int, Gateway>
     */
    public function resolveGateways(
        ?string $recipient = null,
        string $trafficType = 'transactional',
        ?Sender $sender = null,
        ?Gateway $preferredGateway = null,
    ): Collection {
        // 1. Detect Operator and Country from Recipient Number
        $operatorId = null;
        $countryId = null;

        if ($recipient) {
            $digits = preg_replace('/[^\d]/', '', $recipient);
            $operatorCode = null;

            if (str_starts_with($digits, '8801') && strlen($digits) >= 6) {
                $operatorCode = substr($digits, 3, 3); // e.g. 017 or 17
            } elseif (str_starts_with($digits, '01') && strlen($digits) >= 5) {
                $operatorCode = substr($digits, 0, 3); // e.g. 017
            }

            if ($operatorCode) {
                $operator = Operator::where('prefix', $operatorCode)
                    ->orWhere('prefix', ltrim($operatorCode, '0'))
                    ->orWhere('code', $operatorCode)
                    ->first();
                $operatorId = $operator?->id;
            }

            if (str_starts_with($digits, '880') || str_starts_with($digits, '01')) {
                $country = Country::where('iso_code', 'BD')->orWhere('phone_code', '880')->first();
                $countryId = $country?->id;
            }
        }

        // 2. Query Route Rules ordered by priority
        $matchedRule = RouteRule::enabled()
            ->where(function ($q) use ($sender, $operatorId, $countryId, $trafficType) {
                if ($sender) {
                    $q->orWhere('sender_id', $sender->id);
                }
                if ($operatorId) {
                    $q->orWhere('operator_id', $operatorId);
                }
                if ($countryId) {
                    $q->orWhere('country_id', $countryId);
                }
                $q->orWhere('traffic_type', $trafficType);
            })
            ->orderBy('priority', 'asc')
            ->first();

        if ($matchedRule && $matchedRule->routePlan && $matchedRule->routePlan->is_enabled) {
            $gateways = $this->extractGatewaysFromPlan($matchedRule->routePlan);
            if ($gateways->isNotEmpty()) {
                return $gateways;
            }
        }

        // 3. Look for Default Route Plan matching Traffic Type or Global Default
        $defaultPlan = RoutePlan::enabled()
            ->where(function ($q) use ($trafficType) {
                $q->where('traffic_type', $trafficType)
                    ->orWhere('traffic_type', 'all');
            })
            ->where('is_default', true)
            ->first() ?? RoutePlan::enabled()->where('is_default', true)->first();

        if ($defaultPlan) {
            $gateways = $this->extractGatewaysFromPlan($defaultPlan);
            if ($gateways->isNotEmpty()) {
                return $gateways;
            }
        }

        // 4. Default Fallback Chain
        if ($trafficType === 'otp') {
            return Gateway::forOtp()->ordered()->get();
        }

        if ($preferredGateway && $preferredGateway->is_enabled) {
            $otherGateways = Gateway::enabled()
                ->where('id', '!=', $preferredGateway->id)
                ->ordered()
                ->get();

            return collect([$preferredGateway])->merge($otherGateways);
        }

        if ($sender && $sender->gateway && $sender->gateway->is_enabled) {
            $otherGateways = Gateway::enabled()
                ->where('id', '!=', $sender->gateway->id)
                ->ordered()
                ->get();

            return collect([$sender->gateway])->merge($otherGateways);
        }

        return Gateway::enabled()->ordered()->get();
    }

    /**
     * @return Collection<int, Gateway>
     */
    private function extractGatewaysFromPlan(RoutePlan $plan): Collection
    {
        return $plan->planGateways()
            ->where('is_active', true)
            ->with('gateway')
            ->get()
            ->pluck('gateway')
            ->filter(fn (?Gateway $g) => $g !== null && $g->is_enabled && $g->health_status !== 'down')
            ->values();
    }
}
