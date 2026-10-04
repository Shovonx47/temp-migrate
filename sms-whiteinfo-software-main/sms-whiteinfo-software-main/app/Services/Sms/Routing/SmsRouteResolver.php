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
            $digits = ltrim(preg_replace('/[^\d]/', '', $recipient), '+');

            // Find matching country
            $countries = Country::query()->active()->get();
            $matchedCountry = null;

            foreach ($countries as $country) {
                if ($country->code && str_starts_with($digits, (string) $country->code)) {
                    $matchedCountry = $country;
                    break;
                }
            }

            if (! $matchedCountry && (str_starts_with($digits, '01') || str_starts_with($digits, '880'))) {
                $matchedCountry = Country::where('iso_alpha2', 'BD')->orWhere('code', '880')->first();
            }

            if ($matchedCountry) {
                $countryId = $matchedCountry->id;
                $remainder = str_starts_with($digits, (string) $matchedCountry->code)
                    ? substr($digits, strlen((string) $matchedCountry->code))
                    : (str_starts_with($digits, '0') ? substr($digits, 1) : $digits);

                $operators = Operator::query()
                    ->where('country_id', $matchedCountry->id)
                    ->active()
                    ->get();

                foreach ($operators as $op) {
                    foreach ((array) $op->codes as $code) {
                        $codeStr = (string) $code;
                        if ($codeStr !== '' && str_starts_with($remainder, $codeStr)) {
                            $operatorId = $op->id;
                            break 2;
                        }
                    }
                }
            } else {
                $operators = Operator::query()->active()->with('country')->get();
                foreach ($operators as $op) {
                    $countryCode = $op->country?->code ? (string) $op->country->code : '';
                    foreach ((array) $op->codes as $code) {
                        $codeStr = (string) $code;
                        if ($codeStr === '') {
                            continue;
                        }
                        if ($countryCode !== '' && str_starts_with($digits, $countryCode.$codeStr)) {
                            $operatorId = $op->id;
                            $countryId = $op->country_id;
                            break 2;
                        }
                        if (str_starts_with($digits, '0'.$codeStr) || str_starts_with($digits, $codeStr)) {
                            $operatorId = $op->id;
                            $countryId = $op->country_id;
                            break 2;
                        }
                    }
                }
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
