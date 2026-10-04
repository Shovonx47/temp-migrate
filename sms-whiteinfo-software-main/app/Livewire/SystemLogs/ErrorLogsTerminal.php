<?php

namespace App\Livewire\SystemLogs;

use App\Models\SmsLog;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Error Logs terminal — filtered by Provider (Infozillion/gateway-side)
 * or Customer (customer/operator-side).
 *
 * Provider filter: reads real SmsLog DB entries from the Infozillion driver
 * (errors/warnings only) + supplements with log file gateway errors.
 *
 * Customer filter: reads SmsLog DB entries for user-side failures
 * + supplements with log file customer errors.
 */
class ErrorLogsTerminal extends Component
{
    public string $filter = 'provider'; // 'provider' | 'customer'

    /** @var string[] */
    public array $lines = [];

    public bool $autoRefresh = true;

    public int $maxLines = 300;

    public function mount(): void
    {
        $this->loadLogs();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->loadLogs();
    }

    public function loadLogs(): void
    {
        $dbLines = $this->loadFromDatabase();
        $fileLines = $this->loadFromLogFile();

        // Merge: DB entries first (they are the real structured data), then file lines
        $merged = array_merge($dbLines, $fileLines);

        // De-duplicate and limit
        $this->lines = array_slice(array_unique($merged), -$this->maxLines);

        if (empty($this->lines)) {
            $placeholder = $this->filter === 'provider'
                ? 'No provider/gateway errors found.'
                : 'No customer-side errors found.';

            $this->lines = ['['.now()->format('Y-m-d H:i:s')."] [INFO] {$placeholder}"];
        }
    }

    /**
     * Pull real error/warning entries from the sms_logs table.
     *
     * @return string[]
     */
    private function loadFromDatabase(): array
    {
        $query = SmsLog::query()
            ->whereIn('level', ['error', 'warning'])
            ->orderByDesc('id')
            ->limit($this->maxLines);

        if ($this->filter === 'provider') {
            // Gateway-side: Infozillion API errors, connection failures, DLR failures
            $query->where(function ($q) {
                $q->whereNotNull('gateway_name')
                    ->orWhere('event', 'like', '%api_%')
                    ->orWhere('event', 'like', '%connection%')
                    ->orWhere('event', 'like', '%dlr_%')
                    ->orWhere('event', 'like', '%balance_%')
                    ->orWhere('event', 'like', '%bulk_%');
            });
        } else {
            // Customer-side: SMS send failures, exceptions on user operations
            $query->where(function ($q) {
                $q->whereNotNull('user_id')
                    ->orWhere('event', 'like', '%sms_send%')
                    ->orWhere('event', 'like', '%sms_exception%')
                    ->orWhereNotNull('phone');
            })
                ->where(function ($q) {
                    $q->whereNull('gateway_name')
                        ->orWhereNotNull('user_id');
                });
        }

        return $query->get()
            ->reverse()
            ->map(function (SmsLog $log) {
                $ts = $log->created_at?->format('Y-m-d H:i:s');
                $level = strtoupper($log->level?->value ?? 'ERROR');
                $event = strtoupper($log->event);
                $parts = ["[{$ts}]", "[{$level}]", $event];

                if ($log->gateway_name) {
                    $parts[] = "GW={$log->gateway_name}";
                }
                if ($log->phone) {
                    $parts[] = "TO={$log->phone}";
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

        /** @var string[] Provider-side patterns (gateway / Infozillion errors) */
        $providerPatterns = [
            'infozillion', 'gateway', 'anbernet', 'revesms', 'qsms',
            'smsc', 'routing', 'submit_sm', 'deliver_sm',
            'connection refused', 'timeout', 'gateway error',
            'http error', 'curl error', 'ssl error',
        ];

        /** @var string[] Customer-side patterns */
        $customerPatterns = [
            'user_id', 'customer', 'balance', 'insufficient',
            'authentication', 'unauthori', 'sender_required',
            'rate_not_configured', 'campaign', 'batch',
            'message_failed', 'recipient', 'contact',
            'validation', 'template',
        ];

        $patterns = $this->filter === 'provider' ? $providerPatterns : $customerPatterns;
        $remaining = max(0, $this->maxLines - count($this->lines));

        $raw = array_reverse(File::lines($logPath)->all());
        $filtered = [];

        foreach ($raw as $line) {
            $trimmed = trim($line);

            if (empty($trimmed)) {
                continue;
            }

            $lower = strtolower($trimmed);

            $isErrorLine = str_contains($lower, '.error')
                || str_contains($lower, '.warning')
                || str_contains($lower, 'exception')
                || str_contains($lower, 'failed')
                || str_contains($lower, 'error:');

            if (! $isErrorLine) {
                continue;
            }

            $matchesFilter = false;

            foreach ($patterns as $pattern) {
                if (str_contains($lower, $pattern)) {
                    $matchesFilter = true;
                    break;
                }
            }

            if ($matchesFilter) {
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
        $this->lines = ['['.now()->format('Y-m-d H:i:s').'] [INFO] Terminal cleared.'];
    }

    #[On('error-logs-refresh')]
    public function refresh(): void
    {
        $this->loadLogs();
    }

    public function render()
    {
        return view('livewire.system-logs.error-logs-terminal');
    }
}
