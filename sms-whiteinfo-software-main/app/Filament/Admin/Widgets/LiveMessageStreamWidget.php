<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Message;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class LiveMessageStreamWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.live-message-stream';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public int $limit = 30;

    public ?string $statusFilter = null; // 'pending', 'sending', 'dispatched'

    public ?string $sourceFilter = null; // 'api', 'web'

    public ?string $typeFilter = null; // 'quick', 'bulk'

    public string $search = '';

    public bool $isPaused = false;

    public string $viewMode = 'stream'; // 'stream' or 'table'

    #[On('refreshStream')]
    public function refresh(): void
    {
        // Triggers livewire re-render
    }

    public function togglePause(): void
    {
        $this->isPaused = ! $this->isPaused;
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = in_array($mode, ['stream', 'table']) ? $mode : 'stream';
    }

    public function deleteMessage(int $id): void
    {
        $msg = Message::withoutGlobalScopes()->find($id);
        if ($msg) {
            $msg->update([
                'status' => 'failed',
                'failed_reason' => 'Manually cancelled & deleted from queue by Admin',
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
        $msg = Message::withoutGlobalScopes()->find($id);
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
        $affected = Message::withoutGlobalScopes()
            ->whereIn('status', ['pending', 'sending'])
            ->update([
                'status' => 'failed',
                'failed_reason' => 'Purged from live queue by Administrator',
                'updated_at' => now(),
            ]);

        Notification::make()
            ->title("Purged {$affected} stuck queue actions")
            ->success()
            ->body('Queue is now clear.')
            ->duration(4000)
            ->send();
    }

    public function getMessages(): Collection
    {
        $recentThreshold = now()->subSeconds(10);

        $query = Message::withoutGlobalScopes()
            ->with([
                'user:id,name,email,business_name',
                'sender:id,sender_id,type',
                'gateway:id,name,driver',
                'tariffRate:id,tariff_plan_id,operator_id',
                'tariffRate.tariffPlan:id,name',
                'tariffRate.operator:id,name',
                'messageBatch:id,total_recipients,status',
            ]);

        // Strictly filter to live queue / in-flight / immediate actions
        if ($this->statusFilter === 'pending') {
            $query->where('status', 'pending');
        } elseif ($this->statusFilter === 'sending') {
            $query->where('status', 'sending');
        } elseif ($this->statusFilter === 'dispatched') {
            $query->whereIn('status', ['sent', 'delivered'])
                ->where('updated_at', '>=', $recentThreshold);
        } else {
            // Default: In queue right now (pending, sending) OR dispatched in the last 10 seconds
            $query->where(function ($q) use ($recentThreshold) {
                $q->whereIn('status', ['pending', 'sending'])
                    ->orWhere(function ($sub) use ($recentThreshold) {
                        $sub->whereIn('status', ['sent', 'delivered', 'failed', 'dlr_failed'])
                            ->where('updated_at', '>=', $recentThreshold);
                    });
            });
        }

        if ($this->sourceFilter) {
            $query->where('source', $this->sourceFilter);
        }

        if ($this->typeFilter === 'quick') {
            $query->whereNull('batch_id');
        } elseif ($this->typeFilter === 'bulk') {
            $query->whereNotNull('batch_id');
        }

        if (trim($this->search) !== '') {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('to', 'like', $searchTerm)
                    ->orWhere('body', 'like', $searchTerm)
                    ->orWhereHas('user', function ($uq) use ($searchTerm) {
                        $uq->where('name', 'like', $searchTerm)
                            ->orWhere('email', 'like', $searchTerm);
                    });
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
                $timeAgo = $timeDiff <= 5 ? 'Just now (< 5s)' : ($message->updated_at ? $message->updated_at->diffForHumans(short: true) : 'just now');

                return [
                    'id' => $message->id,
                    'customer_name' => $message->user?->name ?: 'Customer #' . $message->user_id,
                    'customer_business' => $message->user?->business_name,
                    'customer_email' => $message->user?->email ?: '',
                    'is_bulk' => $isBulk,
                    'batch_id' => $message->batch_id,
                    'batch_total' => $message->messageBatch?->total_recipients,
                    'action_type' => $isBulk ? 'Bulk SMS' : 'Quick SMS',
                    'sender_id' => $senderId,
                    'is_masking' => $isMasking,
                    'recipient' => $recipient,
                    'body' => (string) $message->body,
                    'status' => $statusVal,
                    'source' => $message->source?->value ?: (string) $message->source,
                    'source_ip' => $message->source_ip ?? null,
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
                    'updated_at_formatted' => $message->updated_at ? $message->updated_at->format('H:i:s') : '',
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

    public function setStatusFilter(?string $status): void
    {
        $this->statusFilter = $this->statusFilter === $status ? null : $status;
    }

    public function setSourceFilter(?string $source): void
    {
        $this->sourceFilter = $this->sourceFilter === $source ? null : $source;
    }

    public function setTypeFilter(?string $type): void
    {
        $this->typeFilter = $this->typeFilter === $type ? null : $type;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = min(max($limit, 10), 100);
    }
}
