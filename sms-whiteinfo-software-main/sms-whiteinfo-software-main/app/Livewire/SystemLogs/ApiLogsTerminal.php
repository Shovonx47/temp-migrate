<?php

namespace App\Livewire\SystemLogs;

use App\Models\SmsLog;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * API Logs terminal — shows real 3rd-party API activity from the sms_logs DB
 * (every Infozillion/gateway HTTP call is captured by the instrumented drivers)
 * plus supplementary entries from the Laravel log file.
 */
class ApiLogsTerminal extends Component
{
    /** @var string[] */
    public array $lines = [];

    public bool $autoRefresh = true;

    public int $maxLines = 500;

    public function mount(): void
    {
        $this->loadLogs();
    }

    public function loadLogs(): void
    {
        $dbLines = $this->loadFromDatabase();
        $fileLines = $this->loadFromLogFile();

        $merged = array_merge($dbLines, $fileLines);

        $this->lines = array_slice(array_unique($merged), -$this->maxLines);

        if (empty($this->lines)) {
            $this->lines = ['['.now()->format('Y-m-d H:i:s').'] [INFO] No significant API activity recorded yet.'];
        }
    }

    /**
     * Load structured API log entries from the sms_logs table.
     * These are REAL entries written by the instrumented gateway drivers.
     *
     * @return string[]
     */
    private function loadFromDatabase(): array
    {
        $logs = SmsLog::query()
            ->where(function ($q) {
                $q->whereNotNull('gateway_name')
                    ->orWhere('event', 'like', '%api_%')
                    ->orWhere('event', 'like', '%bulk_%')
                    ->orWhere('event', 'like', '%dlr_%')
                    ->orWhere('event', 'like', '%balance_%')
                    ->orWhere('event', 'like', '%connection%');
            })
            ->orderByDesc('id')
            ->limit($this->maxLines)
            ->get();

        return $logs->reverse()
            ->map(function (SmsLog $log) {
                $ts = $log->created_at?->format('Y-m-d H:i:s');
                $level = strtoupper($log->level?->value ?? 'INFO');
                $event = strtoupper($log->event);
                $parts = ["[{$ts}]", "[{$level}]", $event];

                if ($log->gateway_name) {
                    $parts[] = "GW={$log->gateway_name}";
                }
                if ($log->phone) {
                    $parts[] = "TO={$log->phone}";
                }
                if ($log->gateway_message_id) {
                    $parts[] = "TXID={$log->gateway_message_id}";
                }
                if ($log->status) {
                    $parts[] = 'STATUS='.strtoupper($log->status);
                }
                if ($log->duration_ms) {
                    $parts[] = "{$log->duration_ms}ms";
                }
                if ($log->error_message) {
                    $parts[] = "ERR=\"{$log->error_message}\"";
                }

                return implode(' ', $parts);
            })
            ->values()
            ->all();
    }

    /**
     * Supplement with entries from the Laravel log file.
     *
     * @return string[]
     */
    private function loadFromLogFile(): array
    {
        $logPath = storage_path('logs/laravel.log');

        if (! File::exists($logPath)) {
            return [];
        }

        $significantPatterns = [
            'gateway', 'infozillion', 'anbernet', 'revesms', 'qsms',
            'api_call', 'http_request', 'http_response', 'webhook',
            'sms_dispatch', 'piprapay', 'payment', 'external', 'outbound',
        ];

        $remaining = max(0, $this->maxLines - count($this->lines));
        $raw = array_reverse(File::lines($logPath)->all());
        $filtered = [];

        foreach ($raw as $line) {
            $trimmed = trim($line);

            if (empty($trimmed)) {
                continue;
            }

            $lower = strtolower($trimmed);
            $isSignificant = false;

            foreach ($significantPatterns as $pattern) {
                if (str_contains($lower, $pattern)) {
                    $isSignificant = true;
                    break;
                }
            }

            if ($isSignificant) {
                $filtered[] = $trimmed;
            }

            if (count($filtered) >= $remaining) {
                break;
            }
        }

        return array_reverse($filtered);
    }

    public function clearTerminal(): void
    {
        $this->lines = ['['.now()->format('Y-m-d H:i:s').'] [INFO] Terminal cleared. Waiting for new API activity...'];
    }

    #[On('api-logs-refresh')]
    public function refresh(): void
    {
        $this->loadLogs();
    }

    public function render()
    {
        return view('livewire.system-logs.api-logs-terminal');
    }
}
