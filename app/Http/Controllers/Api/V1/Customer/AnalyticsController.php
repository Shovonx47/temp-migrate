<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Filament\Concerns\HasDateFilter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AnalyticsController extends Controller
{
    public function messaging(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        [$from, $to] = $this->dateRange($request);

        $stats = $user->messages()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('sent', 'delivered') THEN 1 ELSE 0 END), 0) as successful")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END), 0) as delivered")
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('failed', 'dlr_failed') THEN 1 ELSE 0 END), 0) as failed")
            ->selectRaw("COALESCE(SUM(CASE WHEN status NOT IN ('failed', 'dlr_failed') THEN cost ELSE 0 END), 0) as total_cost")
            ->selectRaw('COALESCE(SUM(parts), 0) as total_parts')
            ->selectRaw('COALESCE(AVG(parts), 0) as avg_parts')
            ->selectRaw('COALESCE(SUM(is_unicode), 0) as unicode')
            ->first();

        $campaigns = $user->batches()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END), 0) as processing")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as completed")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending")
            ->first();

        $daily = $user->messages()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->toBase()
            ->selectRaw('DATE(created_at) as date_val')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('sent', 'delivered') THEN 1 ELSE 0 END), 0) as successful")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END), 0) as delivered")
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('failed', 'dlr_failed') THEN 1 ELSE 0 END), 0) as failed")
            ->selectRaw("COALESCE(SUM(CASE WHEN status NOT IN ('failed', 'dlr_failed') THEN cost ELSE 0 END), 0) as cost")
            ->groupBy('date_val')
            ->orderBy('date_val')
            ->get();

        $total = (int) ($stats->total ?? 0);
        $successful = (int) ($stats->successful ?? 0);

        return new JsonResponse([
            'data' => [
                'period' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ],
                'stats' => [
                    'total' => $total,
                    'successful' => $successful,
                    'delivered' => (int) ($stats->delivered ?? 0),
                    'failed' => (int) ($stats->failed ?? 0),
                    'success_rate' => $total > 0 ? round(($successful / $total) * 100, 1) : 0,
                    'total_cost' => round((float) ($stats->total_cost ?? 0), 2),
                    'total_parts' => (int) ($stats->total_parts ?? 0),
                    'avg_parts' => round((float) ($stats->avg_parts ?? 0), 1),
                    'unicode' => (int) ($stats->unicode ?? 0),
                ],
                'campaigns' => [
                    'total' => (int) ($campaigns->total ?? 0),
                    'processing' => (int) ($campaigns->processing ?? 0),
                    'completed' => (int) ($campaigns->completed ?? 0),
                    'pending' => (int) ($campaigns->pending ?? 0),
                ],
                'daily' => $daily->map(fn ($row): array => [
                    'date' => Carbon::parse($row->date_val)->format('M d'),
                    'total' => (int) $row->total,
                    'successful' => (int) $row->successful,
                    'delivered' => (int) $row->delivered,
                    'failed' => (int) $row->failed,
                    'success_rate' => (int) $row->total > 0
                        ? round(((int) $row->successful / (int) $row->total) * 100, 1)
                        : 0,
                    'cost' => round((float) $row->cost, 2),
                ])->values(),
            ],
        ]);
    }

    public function billing(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        [$from, $to] = $this->dateRange($request);

        $deposits = (float) $user->deposits()
            ->where('status', 'completed')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->sum('amount');

        $spent = (float) $user->messages()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->where('status', '!=', 'failed')
            ->sum('cost');

        $pendingDeposits = (float) $user->deposits()
            ->where('status', 'pending')
            ->sum('amount');

        $transactionCount = $user->transactions()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->count();

        $depositTrend = $user->deposits()
            ->where('status', 'completed')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->toBase()
            ->selectRaw('DATE(created_at) as date_val, SUM(amount) as total')
            ->groupBy('date_val')
            ->orderBy('date_val')
            ->get();

        $spendTrend = $user->messages()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->where('status', '!=', 'failed')
            ->toBase()
            ->selectRaw('DATE(created_at) as date_val, SUM(cost) as total')
            ->groupBy('date_val')
            ->orderBy('date_val')
            ->get();

        $methods = $user->deposits()
            ->where('status', 'completed')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();

        $volume = $user->transactions()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->toBase()
            ->selectRaw('DATE(created_at) as date_val')
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount ELSE 0 END), 0) as credits")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'debit' THEN amount ELSE 0 END), 0) as debits")
            ->groupBy('date_val')
            ->orderBy('date_val')
            ->get();

        return new JsonResponse([
            'data' => [
                'period' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ],
                'stats' => [
                    'deposits' => round($deposits, 2),
                    'spent' => round($spent, 2),
                    'pending_deposits' => round($pendingDeposits, 2),
                    'transactions' => $transactionCount,
                ],
                'deposit_trend' => $depositTrend->map(fn ($row): array => [
                    'date' => Carbon::parse($row->date_val)->format('M d'),
                    'total' => round((float) $row->total, 2),
                ])->values(),
                'spend_trend' => $spendTrend->map(fn ($row): array => [
                    'date' => Carbon::parse($row->date_val)->format('M d'),
                    'total' => round((float) $row->total, 2),
                ])->values(),
                'methods' => $methods->map(fn ($row): array => [
                    'method' => is_object($row->payment_method) && method_exists($row->payment_method, 'getLabel')
                        ? $row->payment_method->getLabel()
                        : (string) $row->payment_method,
                    'total' => round((float) $row->total, 2),
                ])->values(),
                'volume' => $volume->map(fn ($row): array => [
                    'date' => Carbon::parse($row->date_val)->format('M d'),
                    'credits' => round((float) $row->credits, 2),
                    'debits' => round((float) $row->debits, 2),
                ])->values(),
            ],
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function dateRange(Request $request): array
    {
        return HasDateFilter::resolveDateRange([
            'date_range' => $request->string('date_range')->toString() ?: 'last_30_days',
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ]);
    }
}
