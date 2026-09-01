<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Message;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class LiveMessageStreamWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.live-message-stream';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public int $limit = 20;

    public ?string $statusFilter = null;

    public ?string $sourceFilter = null;

    public ?string $typeFilter = null; // 'quick' or 'bulk'

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

    public function getMessages(): Collection
    {
        $query = Message::withoutGlobalScopes()
            ->with([
                'user:id,name,email,business_name',
                'sender:id,sender_id,type',
                'gateway:id,name,driver',
                'tariffRate:id,tariff_plan_id,operator_id',
                'tariffRate.tariffPlan:id,name',
                'tariffRate.operator:id,name',
                'messageBatch:id,total_recipients,status',
            ])
            ->latest('id')
            ->limit($this->limit);

        if ($this->statusFilter) {
            if ($this->statusFilter === 'active') {
                $query->whereIn('status', ['pending', 'sending']);
            } elseif ($this->statusFilter === 'successful') {
                $query->whereIn('status', ['sent', 'delivered']);
            } elseif ($this->statusFilter === 'failed') {
                $query->whereIn('status', ['failed', 'dlr_failed']);
            } else {
                $query->where('status', $this->statusFilter);
            }
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

        return $query->get()->map(function ($message) {
            $recipient = (string) $message->to;
            $operator = $message->tariffRate?->operator?->name ?: $this->detectOperatorFromPhone($recipient);
            $tariffPlan = $message->tariffRate?->tariffPlan?->name ?: 'Standard Rate';

            $isBulk = (bool) $message->batch_id;
            $senderId = $message->sender?->sender_id ?: 'Default CLI';
            $isMasking = $message->sender?->type?->value !== 'numeric';

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
                'status' => $message->status?->value ?: 'pending',
                'source' => $message->source?->value ?: 'web',
                'source_ip' => $message->source_ip ?: null,
                'gateway_name' => $message->gateway?->name ?: 'Unassigned',
                'gateway_driver' => $message->gateway?->driver?->value ?: 'default',
                'operator' => $operator,
                'operator_color' => $this->getOperatorColor($operator),
                'tariff_plan' => $tariffPlan,
                'cost' => (float) ($message->cost ?? 0),
                'rate' => (float) ($message->rate ?? 0),
                'parts' => (int) ($message->parts ?: 1),
                'is_unicode' => (bool) ($message->is_unicode ?? false),
                'failed_reason' => $message->failed_reason,
                'time_ago' => $message->created_at ? $message->created_at->diffForHumans(short: true) : 'just now',
                'timestamp' => $message->created_at ? $message->created_at->format('H:i:s') : now()->format('H:i:s'),
                'created_at_formatted' => $message->created_at ? $message->created_at->format('M d, H:i:s') : '',
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
            default => 'Unknown Operator',
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
