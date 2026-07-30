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
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(cost), 0) as revenue, COALESCE(SUM(gateway_cost), 0) as cost')
            ->first();

        $revenue = (float) ($result->revenue ?? 0);
        $cost = (float) ($result->cost ?? 0);
        $profit = $revenue - $cost;
        $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        return [
            'revenue' => round($revenue, 2),
            'cost' => round($cost, 2),
            'profit' => round($profit, 2),
            'margin' => round($margin, 1),
            'messages' => (int) ($result->count ?? 0),
        ];
    }

    /**
     * Get profit breakdown by operator for a date range.
     *
     * @return array<int, array{operator: string, messages: int, revenue: float, cost: float, profit: float}>
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
                    'cost' => 0.0,
                    'profit' => 0.0,
                ];
            }

            $breakdown[$operatorId]['messages']++;
            $breakdown[$operatorId]['revenue'] += (float) $message->cost;
            $breakdown[$operatorId]['cost'] += (float) $message->gateway_cost;
        }

        foreach ($breakdown as &$item) {
            $item['profit'] = round($item['revenue'] - $item['cost'], 2);
            $item['revenue'] = round($item['revenue'], 2);
            $item['cost'] = round($item['cost'], 2);
        }

        usort($breakdown, fn ($a, $b) => $b['messages'] <=> $a['messages']);

        return array_values($breakdown);
    }

    /**
     * Get profit breakdown by gateway for a date range.
     *
     * @return array<int, array{gateway: string, messages: int, revenue: float, cost: float, profit: float, margin: float}>
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
            ->selectRaw('gateways.name as gateway, COUNT(*) as messages, COALESCE(SUM(messages.cost), 0) as revenue, COALESCE(SUM(messages.gateway_cost), 0) as cost')
            ->groupBy('gateways.name')
            ->orderByDesc('messages')
            ->get();

        return $results->map(function ($row) {
            $revenue = (float) $row->revenue;
            $cost = (float) $row->cost;
            $profit = $revenue - $cost;

            return [
                'gateway' => $row->gateway,
                'messages' => (int) $row->messages,
                'revenue' => round($revenue, 2),
                'cost' => round($cost, 2),
                'profit' => round($profit, 2),
                'margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0,
            ];
        })->toArray();
    }

    /**
     * Get daily profit trend for the last N days.
     *
     * @return array<int, array{date: string, revenue: float, cost: float, profit: float}>
     */
    public function getDailyTrend(int $days = 30): array
    {
        $cacheKey = "profit.trend.{$days}";

        return Cache::remember($cacheKey, 300, function () use ($days) {
            return $this->computeDailyTrend($days);
        });
    }

    private function computeDailyTrend(int $days): array
    {
        $results = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
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
