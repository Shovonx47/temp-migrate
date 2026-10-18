<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LiveQueueController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $fiveMinutesAgo = now()->subMinutes(5);
        $recentThreshold = now()->subSeconds(10);

        $stats = Message::query()
            ->where('created_at', '>=', $fiveMinutesAgo)
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN ("pending","sending") THEN 1 ELSE 0 END) as active_now'),
                DB::raw('SUM(CASE WHEN status IN ("sent","delivered") THEN 1 ELSE 0 END) as success_count'),
            ])
            ->first();

        $total = (int) ($stats->total ?? 0);
        $activeNow = (int) ($stats->active_now ?? 0);
        $successCount = (int) ($stats->success_count ?? 0);

        $statusFilter = $request->string('status')->toString();
        $typeFilter = $request->string('type')->toString();
        $search = trim($request->string('search')->toString());
        $limit = min(max($request->integer('limit', 30), 10), 100);

        $query = Message::query()
            ->with([
                'sender:id,sender_id,type',
                'gateway:id,name,driver',
                'tariffRate:id,tariff_plan_id,operator_id',
                'tariffRate.tariffPlan:id,name',
                'tariffRate.operator:id,name',
                'messageBatch:id,total_recipients,valid_count,invalid_count,status',
            ]);

        if ($statusFilter === 'pending') {
            $query->where('status', 'pending');
        } elseif ($statusFilter === 'sending') {
            $query->where('status', 'sending');
        } elseif ($statusFilter === 'dispatched') {
            $query->whereIn('status', ['sent', 'delivered'])
                ->where('updated_at', '>=', $recentThreshold);
        } else {
            $query->where(function ($q) use ($recentThreshold): void {
                $q->whereIn('status', ['pending', 'sending'])
                    ->orWhere(function ($sub) use ($recentThreshold): void {
                        $sub->whereIn('status', ['sent', 'delivered', 'failed', 'dlr_failed'])
                            ->where('updated_at', '>=', $recentThreshold);
                    });
            });
        }

        if ($typeFilter === 'quick') {
            $query->whereNull('batch_id');
        } elseif ($typeFilter === 'bulk') {
            $query->whereNotNull('batch_id');
        }

        if ($search !== '') {
            $searchTerm = '%'.$search.'%';
            $query->where(function ($q) use ($searchTerm): void {
                $q->where('to', 'like', $searchTerm)
                    ->orWhere('body', 'like', $searchTerm);
            });
        }

        $messages = $query->latest('updated_at')->limit($limit)->get()->map(function (Message $message): array {
            $recipient = (string) $message->to;
            $operator = $message->tariffRate?->operator?->name ?: $this->detectOperatorFromPhone($recipient);
            $isBulk = (bool) $message->batch_id;
            $statusVal = $message->status?->value ?: (string) $message->status;
            $timeDiff = $message->updated_at ? $message->updated_at->diffInSeconds(now()) : 0;

            $batch = $message->messageBatch;
            $batchProgress = null;
            if ($isBulk && $batch) {
                $batchTotal = (int) $batch->total_recipients;
                $processed = (int) $batch->valid_count + (int) $batch->invalid_count;
                $batchProgress = [
                    'id' => $message->batch_id,
                    'total' => $batchTotal,
                    'processed' => $processed,
                    'percent' => $batchTotal > 0 ? (int) floor($processed / $batchTotal * 100) : 0,
                    'status' => $batch->status?->value ?? 'pending',
                ];
            }

            return [
                'id' => $message->id,
                'is_bulk' => $isBulk,
                'batch_id' => $message->batch_id,
                'batch_progress' => $batchProgress,
                'action_type' => $isBulk ? 'Bulk SMS' : 'Quick SMS',
                'sender_id' => $message->sender?->sender_id ?: 'Default CLI',
                'recipient' => $recipient,
                'body' => (string) $message->body,
                'status' => $statusVal,
                'operator' => $operator,
                'cost' => (float) ($message->cost ?? 0),
                'parts' => (int) ($message->parts ?: 1),
                'is_unicode' => (bool) ($message->is_unicode ?? false),
                'failed_reason' => $message->failed_reason,
                'time_ago' => $timeDiff <= 5 ? 'Just now' : ($message->updated_at?->diffForHumans(short: true) ?? 'just now'),
                'timestamp' => $message->updated_at?->format('H:i:s') ?? now()->format('H:i:s'),
            ];
        });

        $batches = Batch::query()
            ->with(['sender:id,sender_id,type'])
            ->where(function ($query): void {
                $query->whereIn('status', ['pending', 'processing'])
                    ->orWhere(function ($sub): void {
                        $sub->whereIn('status', ['completed', 'failed'])
                            ->where('updated_at', '>=', now()->subMinutes(3));
                    });
            })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(function (Batch $batch): array {
                $batchTotal = (int) $batch->total_recipients;
                $processed = (int) $batch->valid_count + (int) $batch->invalid_count;

                return [
                    'id' => $batch->id,
                    'sender_id' => $batch->sender?->sender_id ?? '—',
                    'status' => $batch->status?->value ?? 'pending',
                    'total_recipients' => $batchTotal,
                    'processed' => $processed,
                    'percent' => $batchTotal > 0 ? (int) floor($processed / $batchTotal * 100) : 0,
                    'body' => mb_substr((string) $batch->body, 0, 80),
                    'time_ago' => $batch->created_at?->diffForHumans(short: true) ?? '—',
                ];
            });

        return new JsonResponse([
            'data' => [
                'stats' => [
                    'in_queue' => $activeNow,
                    'msgs_per_min' => $total > 0 ? round($total / 5, 1) : 0,
                    'active_batches' => Batch::query()->where('status', 'processing')->count(),
                    'live_success' => $total > 0 ? round(($successCount / $total) * 100, 1) : 0,
                    'success_count' => $successCount,
                    'total_5m' => $total,
                ],
                'messages' => $messages->values(),
                'batches' => $batches->values(),
            ],
        ]);
    }

    private function detectOperatorFromPhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone) ?? '';
        if (str_starts_with($clean, '880')) {
            $prefix = substr($clean, 3, 2);
        } elseif (str_starts_with($clean, '0')) {
            $prefix = substr($clean, 1, 2);
        } else {
            return 'Mobile Network';
        }

        return match ($prefix) {
            '17', '13' => 'Grameenphone',
            '18' => 'Robi',
            '16' => 'Airtel',
            '19', '14' => 'Banglalink',
            '15' => 'Teletalk',
            default => 'Mobile Operator',
        };
    }
}
