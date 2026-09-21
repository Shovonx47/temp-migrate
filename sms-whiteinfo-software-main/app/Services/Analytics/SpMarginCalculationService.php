<?php

namespace App\Services\Analytics;

use App\Enums\SenderType;
use App\Models\User;
use App\Support\OperatorRateResolver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SpMarginCalculationService
{
    /**
     * S P margin for a period: retail revenue charged to customers minus the S P's wholesale cost.
     *
     * @return array{revenue: float, wholesale_cost: float, margin: float, margin_percent: float, messages: int}
     */
    public function calculateForSp(User $sp, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $sp = ($sp->type->isSp() && $sp->sp_id) ? (User::find($sp->sp_id) ?? $sp) : $sp;
        $from = $from ?? today();
        $to = $to ?? now();

        $cacheKey = 'sp.margin.'.$sp->id.'.'.md5($from->toDateString().$to->toDateString());

        return Cache::remember($cacheKey, 300, function () use ($sp, $from, $to) {
            return $this->computeForSp($sp, $from, $to);
        });
    }

    /**
     * Platform-wide S P margin rollup (all S Ps).
     *
     * @return array{revenue: float, wholesale_cost: float, margin: float, margin_percent: float, messages: int}
     */
    public function calculateForPeriod(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? today();
        $to = $to ?? now();

        $cacheKey = 'sp.margin.all.'.md5($from->toDateString().$to->toDateString());

        return Cache::remember($cacheKey, 300, function () use ($from, $to) {
            $totals = [
                'revenue' => 0.0,
                'wholesale_cost' => 0.0,
                'margin' => 0.0,
                'margin_percent' => 0.0,
                'messages' => 0,
            ];

            $sps = User::query()->sps()->whereNull('sp_id')->get();

            foreach ($sps as $sp) {
                $period = $this->computeForSp($sp, $from, $to);
                $totals['revenue'] += $period['revenue'];
                $totals['wholesale_cost'] += $period['wholesale_cost'];
                $totals['margin'] += $period['margin'];
                $totals['messages'] += $period['messages'];
            }

            $totals['revenue'] = round($totals['revenue'], 2);
            $totals['wholesale_cost'] = round($totals['wholesale_cost'], 2);
            $totals['margin'] = round($totals['margin'], 2);
            $totals['margin_percent'] = $totals['revenue'] > 0
                ? round(($totals['margin'] / $totals['revenue']) * 100, 1)
                : 0.0;

            return $totals;
        });
    }

    /**
     * Per–S P margin breakdown for Superman reporting.
     *
     * @return array<int, array{sp_id: int, sp_name: string, revenue: float, wholesale_cost: float, margin: float, margin_percent: float, messages: int}>
     */
    public function getSpBreakdown(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? today();
        $to = $to ?? now();

        $cacheKey = 'sp.margin.breakdown.'.md5($from->toDateString().$to->toDateString());

        return Cache::remember($cacheKey, 300, function () use ($from, $to) {
            $rows = [];

            foreach (User::query()->sps()->whereNull('sp_id')->orderBy('name')->get() as $sp) {
                $period = $this->computeForSp($sp, $from, $to);

                if ($period['messages'] === 0) {
                    continue;
                }

                $rows[] = [
                    'sp_id' => $sp->id,
                    'sp_name' => $sp->name,
                    ...$period,
                ];
            }

            usort($rows, fn (array $a, array $b): int => $b['margin'] <=> $a['margin']);

            return $rows;
        });
    }

    /**
     * @return array{revenue: float, wholesale_cost: float, margin: float, margin_percent: float, messages: int}
     */
    private function computeForSp(User $sp, Carbon $from, Carbon $to): array
    {
        $messages = DB::table('messages')
            ->join('users', 'messages.user_id', '=', 'users.id')
            ->leftJoin('senders', 'messages.sender_id', '=', 'senders.id')
            ->where('users.sp_id', $sp->id)
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

        if ($messages->isEmpty()) {
            return [
                'revenue' => 0.0,
                'wholesale_cost' => 0.0,
                'margin' => 0.0,
                'margin_percent' => 0.0,
                'messages' => 0,
            ];
        }

        $resolver = OperatorRateResolver::forUser($sp);
        $revenue = 0.0;
        $wholesaleCost = 0.0;

        foreach ($messages as $message) {
            $revenue += (float) $message->cost;
            $parts = max(1, (int) $message->parts);
            $isMasking = $this->isMaskingSenderType($message->sender_type);

            try {
                $wholesaleRate = $resolver->resolve((string) $message->phone, $isMasking);
                $wholesaleCost += $wholesaleRate * $parts;
            } catch (\Throwable) {
                // Missing wholesale config: treat wholesale as 0 so revenue still reports.
            }
        }

        $margin = $revenue - $wholesaleCost;

        return [
            'revenue' => round($revenue, 2),
            'wholesale_cost' => round($wholesaleCost, 2),
            'margin' => round($margin, 2),
            'margin_percent' => $revenue > 0 ? round(($margin / $revenue) * 100, 1) : 0.0,
            'messages' => $messages->count(),
        ];
    }

    private function isMaskingSenderType(mixed $senderType): bool
    {
        if ($senderType === null || $senderType === '') {
            return true;
        }

        $type = $senderType instanceof SenderType
            ? $senderType
            : SenderType::tryFrom((string) $senderType);

        return $type?->isMasking() ?? true;
    }
}
