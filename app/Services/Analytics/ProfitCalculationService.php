<?php

namespace App\Services\Analytics;

use App\Models\Operator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProfitCalculationService
{
    /** @var array<int, Operator> */
    private array $operators = [];

    /** @var array<string, int> "{countryCode}:{opCode}" => operator id */
    private array $codeIndex = [];

    public function __construct()
    {
        $this->loadOperators();
    }

    private function loadOperators(): void
    {
        $operators = Operator::query()
            ->active()
            ->with(['country' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->keyBy('id');

        $this->operators = $operators->all();

        foreach ($operators as $operator) {
            if (! $operator->country) {
                continue;
            }
            $countryCode = (string) $operator->country->code;
            foreach ((array) $operator->codes as $code) {
                $this->codeIndex[$countryCode.':'.(string) $code] = $operator->id;
            }
        }
    }

    public function matchOperator(string $e164Phone): ?Operator
    {
        $digits = ltrim($e164Phone, '+');

        foreach ($this->codeIndex as $key => $operatorId) {
            [$country, $code] = explode(':', $key, 2);

            if (! str_starts_with($digits, $country)) {
                continue;
            }

            $remainder = substr($digits, strlen($country));
            if (str_starts_with($remainder, $code)) {
                return $this->operators[$operatorId] ?? null;
            }
        }

        return null;
    }

    /**
     * Calculate profit for a date range using gateway costs.
     *
     * @return array{revenue: float, cost: float, profit: float, margin: float, messages: int}
     */
    public function calculateForPeriod(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? today();
        $to = $to ?? now();

        $cacheKey = 'profit.'.md5($from->toDateString().$to->toDateString());

        return Cache::remember($cacheKey, 300, function () use ($from, $to) {
            return $this->computeForPeriod($from, $to);
        });
    }

    private function computeForPeriod(Carbon $from, Carbon $to): array
    {
        $result = DB::table('messages')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->where('status', '!=', 'failed')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(cost), 0) as revenue, COALESCE(SUM(operator_cost), 0) as operator_cost, COALESCE(SUM(gateway_cost), 0) as gateway_cost')
            ->first();

        $revenue = (float) ($result->revenue ?? 0);
        $operatorCost = (float) ($result->operator_cost ?? 0);
        $gatewayCost = (float) ($result->gateway_cost ?? 0);
        $totalCost = $operatorCost + $gatewayCost;
        $profit = $revenue - $totalCost;
        $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        return [
            'revenue' => round($revenue, 2),
            'cost' => round($totalCost, 2),
            'operator_cost' => round($operatorCost, 2),
            'gateway_cost' => round($gatewayCost, 2),
            'profit' => round($profit, 2),
            'margin' => round($margin, 1),
            'messages' => (int) ($result->count ?? 0),
        ];
    }

    /**
     * Get profit breakdown by operator for a date range.
     *
     * @return array<int, array{operator: string, messages: int, revenue: float, operator_cost: float, gateway_cost: float, cost: float, profit: float}>
     */
    public function getOperatorBreakdown(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? today();
        $to = $to ?? now();

        $cacheKey = 'profit.operator.'.md5($from->toDateString().$to->toDateString());

        return Cache::remember($cacheKey, 300, function () use ($from, $to) {
            return $this->computeOperatorBreakdown($from, $to);
        });
    }

    private function computeOperatorBreakdown(Carbon $from, Carbon $to): array
    {
        $messages = DB::table('messages')
            ->where('messages.created_at', '>=', $from)
            ->where('messages.created_at', '<=', $to)
            ->where('messages.status', '!=', 'failed')
            ->select([
                'messages.to as phone',
                'messages.cost',
                'messages.operator_cost',
                'messages.gateway_cost',
            ])
            ->get();

        $breakdown = [];

        foreach ($messages as $message) {
            $operator = $this->matchOperator($message->phone);
            $operatorName = $operator?->name ?? 'Unknown';
            $operatorId = $operator?->id ?? 0;

            if (! isset($breakdown[$operatorId])) {
                $breakdown[$operatorId] = [
                    'operator' => $operatorName,
                    'messages' => 0,
                    'revenue' => 0.0,
                    'operator_cost' => 0.0,
                    'gateway_cost' => 0.0,
                    'cost' => 0.0,
                    'profit' => 0.0,
                ];
            }

            $opCost = (float) ($message->operator_cost ?? 0);
            $gwCost = (float) ($message->gateway_cost ?? 0);

            $breakdown[$operatorId]['messages']++;
            $breakdown[$operatorId]['revenue'] += (float) $message->cost;
            $breakdown[$operatorId]['operator_cost'] += $opCost;
            $breakdown[$operatorId]['gateway_cost'] += $gwCost;
            $breakdown[$operatorId]['cost'] += ($opCost + $gwCost);
        }

        foreach ($breakdown as &$item) {
            $item['profit'] = round($item['revenue'] - $item['cost'], 2);
            $item['revenue'] = round($item['revenue'], 2);
            $item['operator_cost'] = round($item['operator_cost'], 2);
            $item['gateway_cost'] = round($item['gateway_cost'], 2);
            $item['cost'] = round($item['cost'], 2);
        }

        usort($breakdown, fn ($a, $b) => $b['messages'] <=> $a['messages']);

        return array_values($breakdown);
    }

    /**
     * Get profit breakdown by gateway for a date range.
     *
     * @return array<int, array{gateway: string, messages: int, revenue: float, operator_cost: float, gateway_cost: float, cost: float, profit: float, margin: float}>
     */
    public function getGatewayBreakdown(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? today();
        $to = $to ?? now();

        $cacheKey = 'profit.gateway.'.md5($from->toDateString().$to->toDateString());

        return Cache::remember($cacheKey, 300, function () use ($from, $to) {
            return $this->computeGatewayBreakdown($from, $to);
        });
    }

    private function computeGatewayBreakdown(Carbon $from, Carbon $to): array
    {
        $results = DB::table('messages')
            ->join('gateways', 'messages.gateway_id', '=', 'gateways.id')
            ->where('messages.created_at', '>=', $from)
            ->where('messages.created_at', '<=', $to)
            ->where('messages.status', '!=', 'failed')
            ->selectRaw('gateways.name as gateway, COUNT(*) as messages, COALESCE(SUM(messages.cost), 0) as revenue, COALESCE(SUM(messages.operator_cost), 0) as operator_cost, COALESCE(SUM(messages.gateway_cost), 0) as gateway_cost')
            ->groupBy('gateways.name')
            ->orderByDesc('messages')
            ->get();

        return $results->map(function ($row) {
            $revenue = (float) $row->revenue;
            $operatorCost = (float) $row->operator_cost;
            $gatewayCost = (float) $row->gateway_cost;
            $totalCost = $operatorCost + $gatewayCost;
            $profit = $revenue - $totalCost;

            return [
                'gateway' => $row->gateway,
                'messages' => (int) $row->messages,
                'revenue' => round($revenue, 2),
                'operator_cost' => round($operatorCost, 2),
                'gateway_cost' => round($gatewayCost, 2),
                'cost' => round($totalCost, 2),
                'profit' => round($profit, 2),
                'margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0,
            ];
        })->toArray();
    }

    /**
     * Get daily profit trend for a date range.
     *
     * @return array<int, array{date: string, revenue: float, cost: float, profit: float}>
     */
    public function getDailyTrend(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? now()->subDays(30);
        $to = $to ?? now();

        $cacheKey = 'profit.trend.'.$from->toDateString().'_'.$to->toDateString();

        return Cache::remember($cacheKey, 300, function () use ($from, $to) {
            return $this->computeDailyTrend($from, $to);
        });
    }

    private function computeDailyTrend(Carbon $from, Carbon $to): array
    {
        $results = [];
        $date = $from->copy()->startOfDay();
        $lastDate = $to->copy()->startOfDay();

        while ($date->lte($lastDate)) {
            $result = $this->computeForPeriod(
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay()
            );

            $results[] = [
                'date' => $date->toDateString(),
                'revenue' => $result['revenue'],
                'cost' => $result['cost'],
                'profit' => $result['profit'],
            ];

            $date->addDay();
        }

        return $results;
    }

    /**
     * Get profit summary for common time periods.
     *
     * @return array<string, array{revenue: float, cost: float, profit: float, margin: float, messages: int}>
     */
    public function getSummary(): array
    {
        $cacheKey = 'profit.summary';

        return Cache::remember($cacheKey, 300, function () {
            return [
                'today' => $this->computeForPeriod(today(), now()),
                'yesterday' => $this->computeForPeriod(
                    now()->subDay()->startOfDay(),
                    now()->subDay()->endOfDay()
                ),
                'this_week' => $this->computeForPeriod(now()->startOfWeek(), now()),
                'this_month' => $this->computeForPeriod(now()->startOfMonth(), now()),
                'this_year' => $this->computeForPeriod(now()->startOfYear(), now()),
                'overall' => $this->computeForPeriod(now()->subYears(10), now()),
            ];
        });
    }
}
