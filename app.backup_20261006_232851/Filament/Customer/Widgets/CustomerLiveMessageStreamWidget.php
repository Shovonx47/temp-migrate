<?php

namespace App\Filament\Customer\Widgets;

use App\Models\Batch;
use App\Models\Message;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class CustomerLiveMessageStreamWidget extends Widget
{
    protected string $view = 'filament.customer.widgets.customer-live-message-stream';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public int $limit = 30;

    public ?string $statusFilter = null; // 'pending', 'sending', 'dispatched'

    public ?string $typeFilter = null; // 'quick', 'bulk'

    public string $search = '';

    public bool $isPaused = false;

    public string $viewMode = 'stream'; // 'stream' or 'table'

    public function togglePause(): void
    {
        $this->isPaused = ! $this->isPaused;
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = in_array($mode, ['stream', 'table']) ? $mode : 'stream';
    }

    public function setStatusFilter(?string $status): void
    {
        $this->statusFilter = $this->statusFilter === $status ? null : $status;
    }

    public function setTypeFilter(?string $type): void
    {
        $this->typeFilter = $this->typeFilter === $type ? null : $type;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = min(max($limit, 10), 100);
    }

    public function getMessages(): Collection
    {
        $recentThreshold = now()->subSeconds(10);

        // OwnedByUser global scope is active — only the logged-in customer's messages are returned
        $query = Message::query()
            ->with([
                'sender:id,sender_id,type',
                'gateway:id,name,driver',
                'tariffRate:id,tariff_plan_id,operator_id',
                'tariffRate.tariffPlan:id,name',
                'tariffRate.operator:id,name',
                'messageBatch:id,total_recipients,valid_count,invalid_count,status',
            ]);

        if ($this->statusFilter === 'pending') {
            $query->where('status', 'pending');
        } elseif ($this->statusFilter === 'sending') {
            $query->where('status', 'sending');
        } elseif ($this->statusFilter === 'dispatched') {
            $query->whereIn('status', ['sent', 'delivered'])
                ->where('updated_at', '>=', $recentThreshold);
        } else {
            // Default: in-flight now OR completed in the last 10 seconds
            $query->where(function ($q) use ($recentThreshold) {
                $q->whereIn('status', ['pending', 'sending'])
                    ->orWhere(function ($sub) use ($recentThreshold) {
                        $sub->whereIn('status', ['sent', 'delivered', 'failed', 'dlr_failed'])
                            ->where('updated_at', '>=', $recentThreshold);
                    });
            });
        }

        if ($this->typeFilter === 'quick') {
            $query->whereNull('batch_id');
        } elseif ($this->typeFilter === 'bulk') {
            $query->whereNotNull('batch_id');
        }

        if (trim($this->search) !== '') {
            $searchTerm = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('to', 'like', $searchTerm)
                    ->orWhere('body', 'like', $searchTerm);
            });
        }

        return $query->latest('updated_at')
            ->limit($this->limit)
            ->get()
            ->map(function ($message) {
                $recipient = (string) $message->to;
                $operator = $message->tariffRate?->operator?->name ?: $this->detectOperatorFromPhone($recipient);
                $tariffPlan = $message->tariffRate?->tariffPlan?->name ?: 'Standard Rate';

                $isBulk = (bool) $message->batch_id;
                $senderId = $message->sender?->sender_id ?: 'Default CLI';
                $isMasking = $message->sender?->type?->value !== 'numeric';

                $statusVal = $message->status?->value ?: (string) $message->status;
                $timeDiff = $message->updated_at ? $message->updated_at->diffInSeconds(now()) : 0;
                $timeAgo = $timeDiff <= 5
                    ? 'Just now (< 5s)'
                    : ($message->updated_at ? $message->updated_at->diffForHumans(short: true) : 'just now');

                // Batch progress for bulk messages
                $batch = $message->messageBatch;
                $batchProgress = null;
                if ($isBulk && $batch) {
                    $total = (int) $batch->total_recipients;
                    $processed = (int) $batch->valid_count + (int) $batch->invalid_count;
                    $batchProgress = [
                        'id' => $message->batch_id,
                        'total' => $total,
                        'processed' => $processed,
                        'percent' => $total > 0 ? (int) floor($processed / $total * 100) : 0,
                        'status' => $batch->status?->value ?? 'pending',
                    ];
                }

                return [
                    'id' => $message->id,
                    'is_bulk' => $isBulk,
                    'batch_id' => $message->batch_id,
                    'batch_progress' => $batchProgress,
                    'action_type' => $isBulk ? 'Bulk SMS' : 'Quick SMS',
                    'sender_id' => $senderId,
                    'is_masking' => $isMasking,
                    'recipient' => $recipient,
                    'body' => (string) $message->body,
                    'status' => $statusVal,
                    'source' => $message->source?->value ?: (string) $message->source,
                    'gateway_name' => $message->gateway?->name ?: 'System Routing Engine',
                    'gateway_driver' => $message->gateway?->driver?->value ?? ($message->gateway?->driver ?: 'default'),
                    'operator' => $operator,
                    'operator_color' => $this->getOperatorColor($operator),
                    'tariff_plan' => $tariffPlan,
                    'cost' => (float) ($message->cost ?? 0),
                    'rate' => (float) ($message->rate ?? 0),
                    'parts' => (int) ($message->parts ?: 1),
                    'is_unicode' => (bool) ($message->is_unicode ?? false),
                    'failed_reason' => $message->failed_reason,
                    'time_ago' => $timeAgo,
                    'timestamp' => $message->updated_at ? $message->updated_at->format('H:i:s') : now()->format('H:i:s'),
                ];
            });
    }

    public function getActiveBatches(): Collection
    {
        // OwnedByUser global scope is active — only this customer's batches are returned
        return Batch::query()
            ->with(['sender:id,sender_id,type'])
            ->where(function ($query) {
                $query->whereIn('status', ['pending', 'processing'])
                    ->orWhere(function ($sub) {
                        $sub->whereIn('status', ['completed', 'failed'])
                            ->where('updated_at', '>=', now()->subMinutes(3));
                    });
            })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(function ($batch) {
                $total = (int) $batch->total_recipients;
                $processed = (int) $batch->valid_count + (int) $batch->invalid_count;

                return [
                    'id' => $batch->id,
                    'sender_id' => $batch->sender?->sender_id ?? '—',
                    'sender_type' => $batch->sender?->type?->value ?? '—',
                    'status' => $batch->status?->value ?? 'pending',
                    'total_recipients' => $total,
                    'processed' => $processed,
                    'percent' => $total > 0 ? (int) floor($processed / $total * 100) : 0,
                    'body' => mb_substr((string) $batch->body, 0, 80),
                    'time_ago' => $batch->created_at?->diffForHumans(short: true) ?? '—',
                ];
            });
    }

    private function detectOperatorFromPhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
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

    private function getOperatorColor(string $operator): string
    {
        return match (strtolower($operator)) {
            'grameenphone' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800',
            'robi' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-300 dark:border-red-800',
            'airtel' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800',
            'banglalink' => 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/40 dark:text-orange-300 dark:border-orange-800',
            'teletalk' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800',
            default => 'bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
        };
    }
}
