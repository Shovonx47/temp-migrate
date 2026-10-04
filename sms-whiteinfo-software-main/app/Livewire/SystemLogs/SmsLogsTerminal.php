<?php

namespace App\Livewire\SystemLogs;

use App\Enums\SmsLogLevel;
use App\Models\SmsLog;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * SMS Logs terminal — live view of the sms_logs database table.
 *
 * Shows paginated history from DB and auto-polls for new entries.
 */
class SmsLogsTerminal extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $lines = [];

    public bool $autoRefresh = true;

    public int $perPage = 200;

    public string $filterLevel = '';

    public string $filterStatus = '';

    /** @var int|null Last loaded ID for incremental updates */
    public ?int $lastId = null;

    public function mount(): void
    {
        $this->loadLogs();
    }

    public function loadLogs(): void
    {
        $query = SmsLog::query()
            ->orderByDesc('id')
            ->limit($this->perPage);

        if ($this->filterLevel !== '') {
            $query->where('level', $this->filterLevel);
        }

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        $logs = $query->get();

        $this->lastId = $logs->first()?->id;

        $this->lines = $logs->reverse()->map(fn (SmsLog $log) => [
            'id' => $log->id,
            'ts' => $log->created_at?->format('Y-m-d H:i:s'),
            'level' => strtoupper($log->level?->value ?? 'info'),
            'event' => strtoupper($log->event),
            'phone' => $log->phone,
            'gateway' => $log->gateway_name,
            'operator' => $log->operator_name,
            'status' => $log->status,
            'error' => $log->error_message,
            'cost' => $log->cost,
            'duration_ms' => $log->duration_ms,
            'parts' => $log->parts,
        ])->values()->toArray();
    }

    public function pollNewLogs(): void
    {
        if (! $this->autoRefresh) {
            return;
        }

        $query = SmsLog::query()->orderBy('id');

        if ($this->lastId) {
            $query->where('id', '>', $this->lastId);
        }

        if ($this->filterLevel !== '') {
            $query->where('level', $this->filterLevel);
        }

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        $newLogs = $query->limit(50)->get();

        if ($newLogs->isEmpty()) {
            return;
        }

        $this->lastId = $newLogs->last()->id;

        $newLines = $newLogs->map(fn (SmsLog $log) => [
            'id' => $log->id,
            'ts' => $log->created_at?->format('Y-m-d H:i:s'),
            'level' => strtoupper($log->level?->value ?? 'info'),
            'event' => strtoupper($log->event),
            'phone' => $log->phone,
            'gateway' => $log->gateway_name,
            'operator' => $log->operator_name,
            'status' => $log->status,
            'error' => $log->error_message,
            'cost' => $log->cost,
            'duration_ms' => $log->duration_ms,
            'parts' => $log->parts,
        ])->values()->toArray();

        // Append and trim to perPage
        $this->lines = array_slice(
            array_merge($this->lines, $newLines),
            -$this->perPage
        );
    }

    public function applyFilters(): void
    {
        $this->lastId = null;
        $this->loadLogs();
    }

    public function clearFilters(): void
    {
        $this->filterLevel = '';
        $this->filterStatus = '';
        $this->lastId = null;
        $this->loadLogs();
    }

    public function clearTerminal(): void
    {
        $this->lines = [];
        $this->lastId = null;
    }

    #[On('sms-logs-refresh')]
    public function refresh(): void
    {
        $this->loadLogs();
    }

    public function getLevelOptions(): array
    {
        return collect(SmsLogLevel::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->name])
            ->all();
    }

    public function getStatusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'sending' => 'Sending',
            'sent' => 'Sent',
            'delivered' => 'Delivered',
            'failed' => 'Failed',
        ];
    }

    public function render()
    {
        return view('livewire.system-logs.sms-logs-terminal', [
            'levelOptions' => $this->getLevelOptions(),
            'statusOptions' => $this->getStatusOptions(),
        ]);
    }
}
