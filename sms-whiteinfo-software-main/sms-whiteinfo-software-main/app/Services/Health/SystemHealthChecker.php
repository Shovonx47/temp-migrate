<?php

namespace App\Services\Health;

use Illuminate\Support\Facades\DB;
use Throwable;

class SystemHealthChecker
{
    /**
     * Run all health checks and return the complete diagnostic dataset.
     *
     * @return array<string, mixed>
     */
    public function runAllChecks(): array
    {
        $db = $this->checkDatabase();
        $phpFpm = $this->checkPhpFpm();
        $nginx = $this->checkNginx();
        $disk = $this->checkDiskSpace();
        $load = $this->checkServerLoad();
        $memory = $this->checkMemory();
        $app = $this->checkApplication();

        // Calculate overall system health
        $statuses = [
            $db['status'],
            $phpFpm['status'],
            $nginx['status'],
            $disk['status'],
            $load['status'],
            $memory['status'],
            $app['status'],
        ];

        $overallStatus = 'healthy';
        if (in_array('critical', $statuses, true)) {
            $overallStatus = 'critical';
        } elseif (in_array('warning', $statuses, true)) {
            $overallStatus = 'warning';
        }

        return [
            'overall' => [
                'status' => $overallStatus,
                'checked_at' => now()->format('Y-m-d H:i:s'),
                'server_time' => now()->toIso8601String(),
                'hostname' => gethostname() ?: 'unknown-host',
                'os' => PHP_OS_FAMILY . ' (' . php_uname('s') . ' ' . php_uname('r') . ')',
            ],
            'database' => $db,
            'php_fpm' => $phpFpm,
            'nginx' => $nginx,
            'disk' => $disk,
            'load' => $load,
            'memory' => $memory,
            'application' => $app,
        ];
    }

    /**
     * Database connectivity check with live PDO connection and latency measurement.
     *
     * @return array<string, mixed>
     */
    public function checkDatabase(): array
    {
        $start = microtime(true);
        $defaultConnection = config('database.default', 'mysql');
        $dbConfig = config("database.connections.{$defaultConnection}", []);

        try {
            // Live connection attempt — not cached
            $pdo = DB::connection($defaultConnection)->getPdo();
            $latencyMs = round((microtime(true) - $start) * 1000, 2);

            // Fetch live server details
            $versionResult = DB::selectOne('SELECT VERSION() as version');
            $databaseResult = DB::selectOne('SELECT DATABASE() as db_name');

            $threadsConnected = null;
            $maxConnections = null;
            try {
                $statusRow = DB::selectOne("SHOW STATUS LIKE 'Threads_connected'");
                if ($statusRow && isset($statusRow->Value)) {
                    $threadsConnected = (int) $statusRow->Value;
                }
                $varRow = DB::selectOne("SHOW VARIABLES LIKE 'max_connections'");
                if ($varRow && isset($varRow->Value)) {
                    $maxConnections = (int) $varRow->Value;
                }
            } catch (Throwable) {
                // Ignore auxiliary status query failures
            }

            $status = 'healthy';
            if ($latencyMs > 500) {
                $status = 'critical';
            } elseif ($latencyMs > 100) {
                $status = 'warning';
            }

            return [
                'status' => $status,
                'latency_ms' => $latencyMs,
                'connection' => $defaultConnection,
                'host' => $dbConfig['host'] ?? '127.0.0.1',
                'port' => $dbConfig['port'] ?? 3306,
                'database' => $databaseResult->db_name ?? ($dbConfig['database'] ?? 'unknown'),
                'server_version' => $versionResult->version ?? 'Unknown',
                'driver' => $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME),
                'active_threads' => $threadsConnected,
                'max_connections' => $maxConnections,
                'error' => null,
            ];
        } catch (Throwable $e) {
            $latencyMs = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'critical',
                'latency_ms' => $latencyMs,
                'connection' => $defaultConnection,
                'host' => $dbConfig['host'] ?? '127.0.0.1',
                'port' => $dbConfig['port'] ?? 3306,
                'database' => $dbConfig['database'] ?? 'unknown',
                'server_version' => 'Unavailable',
                'driver' => $defaultConnection,
                'active_threads' => null,
                'max_connections' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * PHP-FPM service status check and PHP runtime diagnostics.
     *
     * @return array<string, mixed>
     */
    public function checkPhpFpm(): array
    {
        $canExec = $this->isShellExecAvailable();
        $serviceState = 'unable_to_check';
        $serviceChecked = 'php-fpm-84';
        $status = 'healthy';
        $detailMessage = null;

        if ($canExec && PHP_OS_FAMILY !== 'Windows') {
            // Check specific php-fpm-84 service requested
            $output = $this->executeSafeCommand('systemctl is-active php-fpm-84 2>&1');
            $state = trim((string) $output);

            if ($state === 'active') {
                $serviceState = 'active';
                $serviceChecked = 'php-fpm-84';
            } else {
                // Fallback to php8.4-fpm or generic php-fpm check
                $fallback = $this->executeSafeCommand('systemctl is-active php8.4-fpm 2>&1');
                if (trim((string) $fallback) === 'active') {
                    $serviceState = 'active';
                    $serviceChecked = 'php8.4-fpm';
                } elseif (str_contains($state, 'could not be found') || str_contains($state, 'not-found')) {
                    $serviceState = 'not_found';
                    $status = 'warning';
                    $detailMessage = "Service {$serviceChecked} not registered as systemd unit";
                } elseif ($state === 'inactive' || $state === 'failed') {
                    $serviceState = $state;
                    $status = 'critical';
                    $detailMessage = "Service {$serviceChecked} state is {$state}";
                } else {
                    $serviceState = $state ?: 'unknown';
                    $status = 'warning';
                }
            }
        } elseif (PHP_OS_FAMILY === 'Windows') {
            $serviceState = 'dev_cli_server';
            $serviceChecked = 'PHP CLI / Built-in Server (Windows Dev)';
            $detailMessage = 'Running under Windows CLI development server';
        } else {
            $serviceState = 'shell_exec_disabled';
            $status = 'warning';
            $detailMessage = 'shell_exec is disabled in php.ini; service check bypassed';
        }

        // OPcache diagnostics
        $opcacheEnabled = false;
        $opcacheHitRate = null;
        $opcacheUsedMb = null;
        if (function_exists('opcache_get_status')) {
            $opStatus = @opcache_get_status(false);
            if (is_array($opStatus)) {
                $opcacheEnabled = (bool) ($opStatus['opcache_enabled'] ?? false);
                $stats = $opStatus['opcache_statistics'] ?? [];
                $opcacheHitRate = isset($stats['opcache_hit_rate']) ? round($stats['opcache_hit_rate'], 1) : null;
                $mem = $opStatus['memory_usage'] ?? [];
                if (isset($mem['used_memory'])) {
                    $opcacheUsedMb = round($mem['used_memory'] / 1024 / 1024, 1);
                }
            }
        }

        return [
            'status' => $status,
            'service_name' => $serviceChecked,
            'service_state' => $serviceState,
            'detail_message' => $detailMessage,
            'php_version' => PHP_VERSION,
            'sapi_name' => PHP_SAPI,
            'zend_version' => zend_version(),
            'opcache_enabled' => $opcacheEnabled,
            'opcache_hit_rate' => $opcacheHitRate,
            'opcache_used_mb' => $opcacheUsedMb,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . 's',
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ];
    }

    /**
     * Nginx web server service status check.
     *
     * @return array<string, mixed>
     */
    public function checkNginx(): array
    {
        $canExec = $this->isShellExecAvailable();
        $serviceState = 'unable_to_check';
        $status = 'healthy';
        $version = null;
        $detailMessage = null;

        if ($canExec && PHP_OS_FAMILY !== 'Windows') {
            $output = $this->executeSafeCommand('systemctl is-active nginx 2>&1');
            $serviceState = trim((string) $output);

            if ($serviceState === 'active') {
                $status = 'healthy';
                // Try reading version
                $vOut = $this->executeSafeCommand('nginx -v 2>&1');
                if (preg_match('/nginx\/([0-9\.]+)/i', (string) $vOut, $m)) {
                    $version = $m[1];
                }
            } elseif ($serviceState === 'inactive' || $serviceState === 'failed') {
                $status = 'critical';
                $detailMessage = "Nginx systemd service is {$serviceState}";
            } else {
                $status = 'warning';
                $detailMessage = "Nginx service state: " . ($serviceState ?: 'unknown');
            }
        } elseif (PHP_OS_FAMILY === 'Windows') {
            $serviceState = 'dev_environment';
            $status = 'healthy';
            $detailMessage = 'Local environment (serving via php artisan serve / dev host)';
        } else {
            $serviceState = 'shell_exec_disabled';
            $status = 'warning';
            $detailMessage = 'shell_exec disabled in php.ini; unable to verify systemctl';
        }

        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? null;

        return [
            'status' => $status,
            'service_state' => $serviceState,
            'nginx_version' => $version,
            'server_software' => $serverSoftware ?: 'PHP Built-in Server',
            'detail_message' => $detailMessage,
        ];
    }

    /**
     * Root filesystem disk space check (Total, Used, Free in GB and %).
     * Flag Critical above 90% usage, Warning above 80%.
     *
     * @return array<string, mixed>
     */
    public function checkDiskSpace(): array
    {
        $targetPath = PHP_OS_FAMILY === 'Windows' ? base_path() : '/';

        $freeBytes = @disk_free_space($targetPath);
        $totalBytes = @disk_total_space($targetPath);

        if ($freeBytes === false || $totalBytes === false || $totalBytes <= 0) {
            return [
                'status' => 'warning',
                'path' => $targetPath,
                'total_gb' => 0,
                'used_gb' => 0,
                'free_gb' => 0,
                'used_percentage' => 0,
                'free_percentage' => 0,
                'error' => 'Unable to read disk statistics for target filesystem',
            ];
        }

        $usedBytes = max(0, $totalBytes - $freeBytes);
        $usedPercentage = round(($usedBytes / $totalBytes) * 100, 1);
        $freePercentage = round(($freeBytes / $totalBytes) * 100, 1);

        $totalGb = round($totalBytes / (1024 * 1024 * 1024), 2);
        $usedGb = round($usedBytes / (1024 * 1024 * 1024), 2);
        $freeGb = round($freeBytes / (1024 * 1024 * 1024), 2);

        $status = 'healthy';
        if ($usedPercentage >= 90) {
            $status = 'critical';
        } elseif ($usedPercentage >= 80) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'path' => $targetPath,
            'total_gb' => $totalGb,
            'used_gb' => $usedGb,
            'free_gb' => $freeGb,
            'used_percentage' => $usedPercentage,
            'free_percentage' => $freePercentage,
            'error' => null,
        ];
    }

    /**
     * Server load average (1m, 5m, 15m) thresholded against number of CPU cores.
     *
     * @return array<string, mixed>
     */
    public function checkServerLoad(): array
    {
        $cores = $this->getCpuCoreCount();
        $load1 = null;
        $load5 = null;
        $load15 = null;
        $status = 'healthy';
        $detailMessage = null;

        if (function_exists('sys_getloadavg')) {
            $loads = @sys_getloadavg();
            if (is_array($loads) && count($loads) >= 3) {
                $load1 = round($loads[0], 2);
                $load5 = round($loads[1], 2);
                $load15 = round($loads[2], 2);
            }
        }

        // Fallback to /proc/loadavg on Linux
        if ($load1 === null && @file_exists('/proc/loadavg')) {
            $content = @file_get_contents('/proc/loadavg');
            if ($content) {
                $parts = explode(' ', trim($content));
                if (count($parts) >= 3) {
                    $load1 = (float) $parts[0];
                    $load5 = (float) $parts[1];
                    $load15 = (float) $parts[2];
                }
            }
        }

        if ($load1 !== null) {
            // Threshold based on CPU core count
            if ($load1 > ($cores * 2.0)) {
                $status = 'critical';
                $detailMessage = "1-minute load ({$load1}) is severely exceeding CPU capacity ({$cores} cores)";
            } elseif ($load1 > ($cores * 1.0)) {
                $status = 'warning';
                $detailMessage = "1-minute load ({$load1}) is elevated above baseline capacity ({$cores} cores)";
            }
        } else {
            $status = 'healthy';
            $detailMessage = PHP_OS_FAMILY === 'Windows'
                ? 'Load average not natively exposed on Windows OS'
                : 'Unable to read load average from /proc/loadavg';
        }

        return [
            'status' => $status,
            'cpu_cores' => $cores,
            'load_1m' => $load1,
            'load_5m' => $load5,
            'load_15m' => $load15,
            'load_percentage' => ($load1 !== null && $cores > 0) ? round(($load1 / $cores) * 100, 1) : null,
            'detail_message' => $detailMessage,
        ];
    }

    /**
     * Server memory (RAM) check (total, used, free, cached) from /proc/meminfo.
     * Plus PHP's own memory footprint.
     *
     * @return array<string, mixed>
     */
    public function checkMemory(): array
    {
        $totalMb = null;
        $freeMb = null;
        $availableMb = null;
        $usedMb = null;
        $usedPercentage = null;
        $status = 'healthy';
        $detailMessage = null;

        // Try reading /proc/meminfo on Linux
        if (@file_exists('/proc/meminfo')) {
            $meminfo = @file_get_contents('/proc/meminfo');
            if ($meminfo) {
                $data = [];
                foreach (explode("\n", $meminfo) as $line) {
                    if (str_contains($line, ':')) {
                        [$key, $val] = explode(':', $line, 2);
                        $data[trim($key)] = (int) filter_var($val, FILTER_SANITIZE_NUMBER_INT);
                    }
                }

                if (isset($data['MemTotal'])) {
                    $totalKb = $data['MemTotal'];
                    $availKb = $data['MemAvailable'] ?? ($data['MemFree'] ?? 0);
                    $usedKb = max(0, $totalKb - $availKb);

                    $totalMb = round($totalKb / 1024, 1);
                    $availableMb = round($availKb / 1024, 1);
                    $usedMb = round($usedKb / 1024, 1);
                    $freeMb = isset($data['MemFree']) ? round($data['MemFree'] / 1024, 1) : $availableMb;
                    $usedPercentage = $totalKb > 0 ? round(($usedKb / $totalKb) * 100, 1) : 0;
                }
            }
        }

        // Evaluate status threshold
        if ($usedPercentage !== null) {
            if ($usedPercentage >= 90) {
                $status = 'critical';
                $detailMessage = "RAM utilization ({$usedPercentage}%) is critically high";
            } elseif ($usedPercentage >= 80) {
                $status = 'warning';
                $detailMessage = "RAM utilization ({$usedPercentage}%) is approaching capacity";
            }
        }

        // PHP Process memory usage
        $phpCurrentMb = round(memory_get_usage(true) / 1024 / 1024, 2);
        $phpPeakMb = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        return [
            'status' => $status,
            'total_mb' => $totalMb,
            'used_mb' => $usedMb,
            'free_mb' => $freeMb,
            'available_mb' => $availableMb,
            'used_percentage' => $usedPercentage,
            'php_current_mb' => $phpCurrentMb,
            'php_peak_mb' => $phpPeakMb,
            'memory_limit' => ini_get('memory_limit'),
            'detail_message' => $detailMessage,
        ];
    }

    /**
     * Application-level configuration & storage integrity checks.
     *
     * @return array<string, mixed>
     */
    public function checkApplication(): array
    {
        // 1. Maintenance Mode
        $isMaintenance = app()->isDownForMaintenance();

        // 2. storage/app/private writability
        $privatePath = storage_path('app/private');
        $privateDirExists = is_dir($privatePath);
        $privateWritable = false;
        $privateError = null;

        if (! $privateDirExists) {
            @mkdir($privatePath, 0755, true);
            $privateDirExists = is_dir($privatePath);
        }

        if ($privateDirExists) {
            $probeFile = $privatePath . '/.health_probe_' . uniqid('', true);
            $bytesWritten = @file_put_contents($probeFile, 'health-check');
            if ($bytesWritten !== false) {
                $privateWritable = true;
                @unlink($probeFile);
            } else {
                $privateError = 'Failed writing probe test file to directory';
            }
        } else {
            $privateError = 'Directory does not exist and could not be created';
        }

        // 3. APP_KEY set check
        $appKey = (string) config('app.key');
        $hasAppKey = ! empty($appKey);

        // Overall application status
        $status = 'healthy';
        if ($isMaintenance) {
            $status = 'warning';
        }
        if (! $privateWritable || ! $hasAppKey) {
            $status = 'critical';
        }

        return [
            'status' => $status,
            'is_maintenance' => $isMaintenance,
            'maintenance_label' => $isMaintenance ? 'Maintenance Active' : 'Live / Operational',
            'private_storage_path' => $privatePath,
            'private_storage_writable' => $privateWritable,
            'private_storage_error' => $privateError,
            'has_app_key' => $hasAppKey,
            'app_key_cipher' => config('app.cipher', 'AES-256-CBC'),
            'environment' => app()->environment(),
            'debug_mode' => (bool) config('app.debug'),
            'cache_driver' => config('cache.default', 'file'),
            'session_driver' => config('session.driver', 'database'),
        ];
    }

    /**
     * Detect total number of CPU cores.
     */
    private function getCpuCoreCount(): int
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $envCores = getenv('NUMBER_OF_PROCESSORS');
            if ($envCores && is_numeric($envCores)) {
                return (int) $envCores;
            }
        }

        if (@file_exists('/proc/cpuinfo')) {
            $cpuinfo = @file_get_contents('/proc/cpuinfo');
            if ($cpuinfo) {
                preg_match_all('/^processor/m', $cpuinfo, $matches);
                if (! empty($matches[0])) {
                    return count($matches[0]);
                }
            }
        }

        if ($this->isShellExecAvailable()) {
            $nproc = $this->executeSafeCommand('nproc 2>/dev/null');
            if ($nproc && is_numeric(trim((string) $nproc))) {
                return (int) trim((string) $nproc);
            }
        }

        return 1;
    }

    /**
     * Check whether shell_exec is available and not disabled in php.ini.
     */
    public function isShellExecAvailable(): bool
    {
        if (! function_exists('shell_exec')) {
            return false;
        }

        $disabled = explode(',', (string) ini_get('disable_functions'));
        $disabled = array_map('trim', $disabled);

        return ! in_array('shell_exec', $disabled, true);
    }

    /**
     * Safely execute a read-only shell command with error suppression.
     */
    private function executeSafeCommand(string $command): ?string
    {
        if (! $this->isShellExecAvailable()) {
            return null;
        }

        try {
            return @shell_exec($command);
        } catch (Throwable) {
            return null;
        }
    }
}
