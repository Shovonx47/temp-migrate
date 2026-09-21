@php
    $health = $this->getHealth();
    $overall = $health['overall'] ?? [];
    $db = $health['database'] ?? [];
    $phpFpm = $health['php_fpm'] ?? [];
    $nginx = $health['nginx'] ?? [];
    $disk = $health['disk'] ?? [];
    $load = $health['load'] ?? [];
    $memory = $health['memory'] ?? [];
    $app = $health['application'] ?? [];

    $overallStatus = $overall['status'] ?? 'healthy';
    $diskUsedPct = $disk['used_percentage'] ?? 0;
    $ramUsedPct = $memory['used_percentage'] ?? null;
@endphp

<div wire:poll.10s class="w-full">
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
        {{-- Header Bar --}}
        <div class="p-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400">
                        <x-heroicon-o-cpu-chip class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-bold text-gray-900 dark:text-white tracking-tight">System & Infrastructure Health</h2>
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold border
                                {{ $overallStatus === 'healthy'
                                    ? 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800'
                                    : ($overallStatus === 'warning'
                                        ? 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-400 dark:border-amber-800'
                                        : 'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-950/50 dark:text-rose-400 dark:border-rose-800') }}">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $overallStatus === 'healthy' ? 'bg-emerald-400' : ($overallStatus === 'warning' ? 'bg-amber-400' : 'bg-rose-400') }} opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 {{ $overallStatus === 'healthy' ? 'bg-emerald-500' : ($overallStatus === 'warning' ? 'bg-amber-500' : 'bg-rose-500') }}"></span>
                                </span>
                                {{ strtoupper($overallStatus === 'healthy' ? 'ALL SYSTEMS OPERATIONAL' : $overallStatus) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Live engine diagnostics &bull; Database PDO latency, PHP-FPM 8.4, Nginx, Disk, RAM, and KYC storage
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-[11px] text-gray-400">
                        Host: <span class="font-mono font-medium text-gray-600 dark:text-gray-300">{{ $overall['hostname'] ?? 'server' }}</span> &bull; 10s poll
                    </span>
                    <a
                        href="{{ url('/superman/monitoring/system-health') }}"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 ml-2"
                    >
                        <span>Full Diagnostics</span>
                        <x-heroicon-m-arrow-top-right-on-square class="w-3.5 h-3.5" />
                    </a>
                </div>
            </div>
        </div>

        {{-- Main Grid of Health Cards --}}
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
                {{-- 1. Database Connectivity --}}
                <div class="p-3.5 rounded-xl border transition-all
                    {{ ($db['status'] ?? '') === 'healthy'
                        ? 'border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40'
                        : (($db['status'] ?? '') === 'warning'
                            ? 'border-amber-300 dark:border-amber-800 bg-amber-50/40 dark:bg-amber-950/20'
                            : 'border-rose-300 dark:border-rose-800 bg-rose-50/40 dark:bg-rose-950/20') }}">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Database Connection</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.2 rounded
                            {{ ($db['status'] ?? '') === 'healthy' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400' }}">
                            {{ strtoupper($db['status'] ?? 'unknown') }}
                        </span>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-xl font-bold font-mono {{ ($db['latency_ms'] ?? 0) < 50 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                            {{ $db['latency_ms'] ?? '—' }}
                        </span>
                        <span class="text-xs text-gray-400 font-mono">ms</span>
                    </div>
                    <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-800 text-[11px] text-gray-500 dark:text-gray-400">
                        <div class="truncate font-semibold text-gray-700 dark:text-gray-300">{{ $db['database'] ?? 'mysql' }}</div>
                        <div class="text-[10px] text-gray-400">{{ $db['server_version'] ?? 'MariaDB' }}</div>
                    </div>
                </div>

                {{-- 2. PHP-FPM Service (php-fpm-84) --}}
                <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">PHP-FPM 8.4</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.2 rounded
                            {{ ($phpFpm['status'] ?? '') === 'healthy' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400' }}">
                            {{ strtoupper($phpFpm['service_state'] ?? 'active') }}
                        </span>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-xl font-bold font-mono text-gray-900 dark:text-white">v{{ $phpFpm['php_version'] ?? PHP_VERSION }}</span>
                    </div>
                    <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-800 text-[11px] text-gray-500 dark:text-gray-400">
                        <div>Mem Limit: <span class="font-mono font-semibold text-gray-700 dark:text-gray-300">{{ $phpFpm['memory_limit'] ?? '512M' }}</span></div>
                        <div class="text-[10px] text-gray-400">OPcache: {{ ($phpFpm['opcache_enabled'] ?? false) ? 'Active (' . ($phpFpm['opcache_hit_rate'] ?? 0) . '%)' : 'CLI/Disabled' }}</div>
                    </div>
                </div>

                {{-- 3. Nginx Web Server --}}
                <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Nginx Server</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.2 rounded
                            {{ ($nginx['status'] ?? '') === 'healthy' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400' }}">
                            {{ strtoupper($nginx['service_state'] ?? 'active') }}
                        </span>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-base font-bold font-mono text-gray-900 dark:text-white truncate">
                            {{ $nginx['nginx_version'] ? 'Nginx v' . $nginx['nginx_version'] : ($nginx['server_software'] ?? 'Nginx') }}
                        </span>
                    </div>
                    <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-800 text-[11px] text-gray-500 dark:text-gray-400">
                        <div>SSL: <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ request()->isSecure() ? 'HTTPS Active' : 'HTTP' }}</span></div>
                        <div class="text-[10px] text-gray-400 truncate">Port {{ request()->getPort() }}</div>
                    </div>
                </div>

                {{-- 4. Root Disk Space --}}
                <div class="p-3.5 rounded-xl border transition-all
                    {{ $diskUsedPct >= 90 ? 'border-rose-300 dark:border-rose-800 bg-rose-50/40 dark:bg-rose-950/20' : ($diskUsedPct >= 80 ? 'border-amber-300 dark:border-amber-800 bg-amber-50/40 dark:bg-amber-950/20' : 'border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40') }}">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Root Disk</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.2 rounded
                            {{ $diskUsedPct >= 90 ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400' : ($diskUsedPct >= 80 ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400') }}">
                            {{ $diskUsedPct }}% USED
                        </span>
                    </div>
                    <div class="flex items-baseline justify-between mb-1">
                        <span class="text-base font-bold font-mono text-gray-900 dark:text-white">{{ $disk['free_gb'] ?? 0 }} GB</span>
                        <span class="text-[10px] text-gray-400">free of {{ $disk['total_gb'] ?? 0 }}G</span>
                    </div>
                    {{-- Mini Progress Bar --}}
                    <div class="w-full h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden mb-1">
                        <div class="h-full rounded-full transition-all duration-500
                            {{ $diskUsedPct >= 90 ? 'bg-rose-500' : ($diskUsedPct >= 80 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                            style="width: {{ min(100, $diskUsedPct) }}%"></div>
                    </div>
                    <div class="text-[10px] text-gray-400 truncate mt-1">Used: {{ $disk['used_gb'] ?? 0 }} GB</div>
                </div>

                {{-- 5. Server Memory (RAM) --}}
                <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Server RAM</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-purple-100 text-purple-700 dark:bg-purple-950/50 dark:text-purple-400">
                            {{ $ramUsedPct !== null ? $ramUsedPct . '% USED' : 'PHP ' . ($memory['php_current_mb'] ?? 0) . 'M' }}
                        </span>
                    </div>
                    <div class="flex items-baseline justify-between mb-1">
                        @if($memory['total_mb'] ?? false)
                            <span class="text-base font-bold font-mono text-gray-900 dark:text-white">{{ round(($memory['available_mb'] ?? 0) / 1024, 1) }} GB</span>
                            <span class="text-[10px] text-gray-400">avail of {{ round(($memory['total_mb'] ?? 0) / 1024, 1) }}G</span>
                        @else
                            <span class="text-base font-bold font-mono text-gray-900 dark:text-white">{{ $memory['php_current_mb'] ?? 0 }} MB</span>
                            <span class="text-[10px] text-gray-400">PHP heap</span>
                        @endif
                    </div>
                    <div class="w-full h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden mb-1">
                        <div class="h-full bg-purple-500 rounded-full" style="width: {{ $ramUsedPct !== null ? min(100, $ramUsedPct) : 35 }}%"></div>
                    </div>
                    <div class="text-[10px] text-gray-400 truncate mt-1">Peak: {{ $memory['php_peak_mb'] ?? 0 }} MB</div>
                </div>

                {{-- 6. Application & Storage Health --}}
                <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">KYC Storage & App</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.2 rounded
                            {{ ($app['private_storage_writable'] ?? false) ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400' }}">
                            {{ ($app['private_storage_writable'] ?? false) ? 'WRITABLE' : 'DENIED' }}
                        </span>
                    </div>
                    <div class="text-sm font-bold text-emerald-600 dark:text-emerald-400 truncate">
                        {{ $app['maintenance_label'] ?? 'Live / Active' }}
                    </div>
                    <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-800 text-[11px] text-gray-500 dark:text-gray-400">
                        <div>APP_KEY: <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ ($app['has_app_key'] ?? false) ? 'Set' : 'Missing' }}</span></div>
                        <div class="text-[10px] text-gray-400 truncate">KYC Uploads: Ready</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
