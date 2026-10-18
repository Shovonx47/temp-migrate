<?php

namespace App\Filament\Sp\Widgets;

use App\Models\Batch;
use App\Models\Message;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class SpLiveMessageStreamWidget extends Widget
{
    protected string $view = 'filament.sp.widgets.sp-live-message-stream';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public int $limit = 30;

    public ?int $selectedCustomerId = null;

    public ?string $statusFilter = null; // 'pending', 'sending', 'dispatched', 'failed'

    public ?string $typeFilter = null; // 'quick', 'bulk'

    public string $search = '';

    public bool $isPaused = false;

    public string $viewMode = 'stream'; // 'stream' or 'table'

    public function togglePause(): void
    {
        $this->isPaused = ! $this->isPaused;

        Notification::make()
            ->title($this->isPaused ? 'Live queue stream paused' : 'Live queue stream resumed')
            ->info()
            ->duration(2000)
            ->send();
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = in_array($mode, ['stream', 'table'], true) ? $mode : 'stream';
    }

    public function setStatusFilter(?string $status): void
    {
        $this->statusFilter = $this->statusFilter === $status ? null : $status;
    }

    public function setTypeFilter(?string $type): void
    {
        $this->typeFilter = $this->typeFilter === $type ? null : $type;
    }

    public function setCustomerFilter(?int $customerId): void
    {
        $this->selectedCustomerId = $customerId ?: null;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = min(max($limit, 10), 100);
    }

    public function deleteMessage(int $id): void
    {
        $customerIds = $this->getSpCustomerIds();
        $msg = Message::withoutGlobalScopes()
            ->whereIn('user_id', $customerIds)
            ->find($id);

        if ($msg) {
            $msg->update([
                'status' => 'failed',
                'failed_reason' => 'Manually cancelled & deleted from queue by SP Reseller',
                'updated_at' => now(),
            ]);

            Notification::make()
                ->title("SMS Action #{$id} removed from queue")
                ->success()
                ->duration(3000)
                ->send();
        }
    }

    public function retrySingleMessage(int $id): void
    {
        $customerIds = $this->getSpCustomerIds();
        $msg = Message::withoutGlobalScopes()
            ->whereIn('user_id', $customerIds)
            ->find($id);

        if ($msg) {
            $msg->update([
                'status' => 'pending',
                'failed_reason' => null,
                'updated_at' => now(),
            ]);

            Notification::make()
                ->title("SMS Action #{$id} requeued for live send")
                ->success()
                ->duration(3000)
                ->send();
        }
    }

    public function purgeStaleQueue(): void
    {
        $customerIds = $this->getSpCustomerIds();
        if (empty($customerIds)) {
            return;
        }

        $threshold = now()->subMinutes(8);

        $purgedMessages = Message::withoutGlobalScopes()
            ->whereIn('user_id', $customerIds)
            ->whereIn('status', ['pending', 'sending'])
            ->where('updated_at', '<=', $threshold)
            ->update([
                'status' => 'failed',
                'failed_reason' => 'Auto-purged: stuck in SP queue for more than 8 minutes without progress',
                'updated_at' => now(),
            ]);

        $purgedBatches = Batch::withoutGlobalScopes()
            ->whereIn('user_id', $customerIds)
            ->whereIn('status', ['pending', 'processing'])
            ->where('updated_at', '<=', $threshold)
            ->update([
                'status' => 'failed',
                'updated_at' => now(),
            ]);

        $total = $purgedMessages + $purgedBatches;

        if ($total > 0) {
            Notification::make()
                ->title("Queue Purged: {$total} stale items cleared")
                ->success()
                ->body("Cleared {$purgedMessages} stuck message(s) and {$purgedBatches} stuck batch(es) older than 8 minutes.")
                ->duration(5000)
                ->send();
        } else {
            Notification::make()
                ->title('Queue is Clean')
                ->info()
                ->body('No items stuck older than 8 minutes found in your customers\' queue.')
                ->duration(3000)
                ->send();
        }
    }

    public function requeueStuck(): void
    {
        $customerIds = $this->getSpCustomerIds();
        if (empty($customerIds)) {
            return;
        }

        $requeued = Message::withoutGlobalScopes()
            ->whereIn('user_id', $customerIds)
            ->where('status', 'sending')
            ->where('updated_at', '<=', now()->subMinutes(2))
            ->update([
                'status' => 'pending',
                'updated_at' => now(),
            ]);

        Notification::make()
            ->title("Requeued {$requeued} in-flight message(s)")
            ->info()
            ->body('Stuck in-flight dispatches reset to pending state.')
            ->duration(3000)
            ->send();
    }

    public function getSpCustomerIds(): array
    {
        /** @var User|null $actingUser */
        $actingUser = Filament::auth()->user();
        if (! $actingUser) {
            return [];
        }

        $sp = ($actingUser->type->isSp() && $actingUser->sp_id)
            ? (User::find($actingUser->sp_id) ?? $actingUser)
            : $actingUser;

        return $sp->spCustomers()->pluck('id')->all();
    }

    public function getCustomersList(): Collection
    {
        $customerIds = $this->getSpCustomerIds();
        if (empty($customerIds)) {
            return collect();
        }

        return User::withoutGlobalScopes()
            ->whereIn('id', $customerIds)
            ->select(['id', 'name', 'business_name', 'email'])
            ->orderBy('name')
            ->get();
    }

    public function getMessages(): Collection
    {
        $customerIds = $this->getSpCustomerIds();
        if (empty($customerIds)) {
            return collect();
        }

        $recentThreshold = now()->subSeconds(10);
        $staleThreshold = now()->subMinutes(8);

        $query = Message::withoutGlobalScopes()
            ->whereIn('user_id', $customerIds)
            ->with([
                'user:id,name,email,business_name',
                'sender:id,sender_id,type',
                'gateway:id,name,driver',
                'tariffRate:id,tariff_plan_id,operator_id',
                'tariffRate.tariffPlan:id,name',
                'tariffRate.operator:id,name',
                'messageBatch:id,total_recipients,valid_count,invalid_count,status',
            ]);

        if ($this->selectedCustomerId) {
            $query->where('user_id', $this->selectedCustomerId);
        }

        if ($this->statusFilter === 'pending') {
            $query->where('status', 'pending');
        } elseif ($this->statusFilter === 'sending') {
            $query->where('status', 'sending');
        } elseif ($this->statusFilter === 'dispatched') {
            $query->whereIn('status', ['sent', 'delivered'])
                ->where('updated_at', '>=', $recentThreshold);
        } elseif ($this->statusFilter === 'failed') {
            $query->whereIn('status', ['failed', 'dlr_failed'])
                ->where('updated_at', '>=', now()->subHours(1));
        } else {
            // Default: in-flight (pending, sending) OR recent dispatched in last 10 seconds
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
                    ->orWhere('body', 'like', $searchTerm)
                    ->orWhereHas('user', function ($uq) use ($searchTerm) {
                        $uq->where('name', 'like', $searchTerm)
                            ->orWhere('business_name', 'like', $searchTerm);
                    });
            });
        }

        return $query->latest('updated_at')
            ->limit($this->limit)
            ->get()
            ->map(function ($message) use ($staleThreshold) {
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

                $isStale = in_array($statusVal, ['pending', 'sending'], true)
                    && $message->updated_at
                    && $message->updated_at->lte($staleThreshold);

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

                $customerName = $message->user?->name ?: 'Customer #'.$message->user_id;
                $customerBusiness = $message->user?->business_name;

                return [
                    'id' => $message->id,
                    'customer_id' => $message->user_id,
                    'customer_name' => $customerName,
                    'customer_business' => $customerBusiness,
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
                    'is_stale' => $isStale,
                ];
            });
    }

    public function getActiveBatches(): Collection
    {
        $customerIds = $this->getSpCustomerIds();
        if (empty($customerIds)) {
            return collect();
        }

        $query = Batch::withoutGlobalScopes()
            ->whereIn('user_id', $customerIds)
            ->with(['user:id,name,business_name', 'sender:id,sender_id,type'])
            ->where(function ($q) {
                $q->whereIn('status', ['pending', 'processing'])
                    ->orWhere(function ($sub) {
                        $sub->whereIn('status', ['completed', 'failed'])
                            ->where('updated_at', '>=', now()->subMinutes(3));
                    });
            });

        if ($this->selectedCustomerId) {
            $query->where('user_id', $this->selectedCustomerId);
        }

        return $query->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(function ($batch) {
                $total = (int) $batch->total_recipients;
                $processed = (int) $batch->valid_count + (int) $batch->invalid_count;

                return [
                    'id' => $batch->id,
                    'customer_name' => $batch->user?->name ?: 'Customer #'.$batch->user_id,
                    'customer_business' => $batch->user?->business_name,
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
