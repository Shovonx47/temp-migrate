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
     * Calculate profit for a date range.
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
        $messages = DB::table('messages')
            ->join('senders', 'messages.sender_id', '=', 'senders.id')
            ->where('messages.created_at', '>=', $from)
            ->where('messages.created_at', '<=', $to)
            ->where('messages.status', '!=', 'failed')
            ->select([
                'messages.to as phone',
                'messages.cost',
                'messages.parts',
                'messages.rate',
                'senders.type as sender_type',
            ])
            ->get();

        $revenue = 0.0;
        $cost = 0.0;
        $count = $messages->count();

        foreach ($messages as $message) {
            $revenue += (float) $message->cost;

            $operator = $this->matchOperator($message->phone);
            if ($operator === null) {
                continue;
            }

            $isMasking = $message->sender_type !== 'numeric';
            $buyingRate = $isMasking
                ? ($operator->masking_price ?? 0)
                : ($operator->non_masking_price ?? 0);

            $cost += $buyingRate * (int) $message->parts;
        }

        $profit = $revenue - $cost;
        $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        return [
            'revenue' => round($revenue, 2),
            'cost' => round($cost, 2),
            'profit' => round($profit, 2),
            'margin' => round($margin, 1),
            'messages' => $count,
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
            ->join('senders', 'messages.sender_id', '=', 'senders.id')
            ->where('messages.created_at', '>=', $from)
            ->where('messages.created_at', '<=', $to)
            ->where('messages.status', '!=', 'failed')
            ->select([
                'messages.to as phone',
                'messages.cost',
                'messages.parts',
                'senders.type as sender_type',
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

            if ($operator !== null) {
                $isMasking = $message->sender_type !== 'numeric';
                $buyingRate = $isMasking
                    ? ($operator->masking_price ?? 0)
                    : ($operator->non_masking_price ?? 0);
                $breakdown[$operatorId]['cost'] += $buyingRate * (int) $message->parts;
            }
        }

        foreach ($breakdown as &$item) {
            $item['profit'] = round($item['revenue'] - $item['cost'], 2);
            $item['revenue'] = round($item['revenue'], 2);
            $item['cost'] = round($item['cost'], 2);
        }

        // Sort by messages descending
        usort($breakdown, fn ($a, $b) => $b['messages'] <=> $a['messages']);

        return array_values($breakdown);
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
