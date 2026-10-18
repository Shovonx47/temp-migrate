<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Batch;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class LiveBatchMonitorWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.live-batch-monitor';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function getActiveBatches(): Collection
    {
        return Batch::withoutGlobalScopes()
            ->with(['user:id,name,email', 'sender:id,sender_id,type'])
            ->where(function ($query) {
                $query->whereIn('status', ['pending', 'processing'])
                    ->orWhere(function ($sub) {
                        $sub->whereIn('status', ['completed', 'failed'])
                            ->where('updated_at', '>=', now()->subMinutes(5));
                    });
            })
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get()
            ->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'customer_name' => $batch->user?->name ?? 'Unknown',
                    'customer_email' => $batch->user?->email ?? '',
                    'sender_id' => $batch->sender?->sender_id ?? '—',
                    'sender_type' => $batch->sender?->type?->value ?? '—',
                    'source_type' => $batch->source_type?->value ?? '—',
                    'status' => $batch->status?->value ?? 'pending',
                    'approval' => $batch->approval?->value ?? '—',
                    'total_recipients' => $batch->total_recipients ?? 0,
                    'valid_count' => $batch->valid_count ?? 0,
                    'invalid_count' => $batch->invalid_count ?? 0,
                    'progress' => $batch->progress_percent ?? 0,
                    'progress_label' => $batch->progress_label ?? '—',
                    'body' => mb_substr((string) $batch->body, 0, 80),
                    'source_ip' => $batch->source_ip ?? '—',
                    'time_ago' => $batch->created_at?->diffForHumans(short: true) ?? '—',
                ];
            });
    }
}
