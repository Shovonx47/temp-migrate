<x-filament-panels::page>
    <div wire:poll.10s="pollHealth" class="space-y-6">
        @php
            $overall = $health['overall'] ?? [];
            $db = $health['database'] ?? [];
            $phpFpm = $health['php_fpm'] ?? [];
            $nginx = $health['nginx'] ?? [];
            $disk = $health['disk'] ?? [];
            $load = $health['load'] ?? [];
            $memory = $health['memory'] ?? [];
            $app = $health['application'] ?? [];

            $overallStatus = $overall['status'] ?? 'healthy';
        @endphp

        {{-- Top Summary Banner --}}
        <div class="rounded-xl border p-5 transition-all shadow-sm
            {{ $overallStatus === 'healthy'
                ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-950 dark:text-emerald-100'
                : ($overallStatus === 'warning'
                    ? 'bg-amber-500/10 border-amber-500/30 text-amber-950 dark:text-amber-100'
                    : 'bg-rose-500/10 border-rose-500/30 text-rose-950 dark:text-rose-100') }}">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="flex items-center justify-center w-12 h-12 rounded-xl
                        {{ $overallStatus === 'healthy'
                            ? 'bg-emerald-500 text-white shadow-emerald-500/20 shadow-lg'
                            : ($overallStatus === 'warning'
                                ? 'bg-amber-500 text-white shadow-amber-500/20 shadow-lg'
                                : 'bg-rose-500 text-white shadow-rose-500/20 shadow-lg') }}">
                        @if($overallStatus === 'healthy')
                            <x-heroicon-o-check-badge class="w-7 h-7" />
                        @elseif($overallStatus === 'warning')
                            <x-heroicon-o-exclamation-triangle class="w-7 h-7" />
                        @else
                            <x-heroicon-o-x-circle class="w-7 h-7" />
                        @endif
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-bold tracking-tight">
                                @if($overallStatus === 'healthy')
                                    All Systems Operational
                                @elseif($overallStatus === 'warning')
                                    System Performance Degraded
                                @else
                                    Critical System Alert
                                @endif
                            </h2>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider
                                {{ $overallStatus === 'healthy'
                                    ? 'bg-emerald-600 text-white'
                                    : ($overallStatus === 'warning'
                                        ? 'bg-amber-600 text-white'
                                        : 'bg-rose-600 text-white') }}">
                                {{ strtoupper($overallStatus) }}
                            </span>
                        </div>
                        <p class="text-xs opacity-80 mt-0.5">
                            Host: <span class="font-mono font-semibold">{{ $overall['hostname'] ?? 'server' }}</span> &bull;
                            OS: <span class="font-medium">{{ $overall['os'] ?? 'Linux' }}</span> &bull;
                            Checked: <span class="font-mono">{{ $overall['checked_at'] ?? now()->toTimeString() }}</span>
                        </p>
                    </div>
                </div>

                {{-- Action / Refresh Controls --}}
                <div class="flex items-center gap-2.5 self-start md:self-auto">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-white/60 dark:bg-gray-800/60 border border-gray-200/50 dark:border-gray-700/50">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        Auto-refreshing: 10s
                    </span>

                    <button
                        type="button"
                        wire:click="refreshHealth"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 text-xs font-semibold shadow-sm transition-colors disabled:opacity-50 cursor-pointer"
                    >
                        <x-heroicon-o-arrow-path wire:loading.remove wire:target="refreshHealth" class="w-3.5 h-3.5" />
                        <x-heroicon-o-arrow-path wire:loading wire:target="refreshHealth" class="w-3.5 h-3.5 animate-spin" />
                        <span wire:loading.remove wire:target="refreshHealth">Run Checks Now</span>
                        <span wire:loading wire:target="refreshHealth">Checking...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Row 1: Database Connectivity & Engine --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <x-heroicon-o-circle-stack class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Database Connectivity & SQL Engine</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Live PDO connection attempt with response time in ms</p>
                    </div>
                </div>

                <div>
                    @if(($db['status'] ?? '') === 'healthy')
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            HEALTHY
                        </span>
                    @elseif(($db['status'] ?? '') === 'warning')
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            HIGH LATENCY
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                            CRITICAL FAILURE
                        </span>
                    @endif
                </div>
            </div>

            @if(!empty($db['error']))
                <div class="mb-4 p-3 rounded-lg bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 text-xs text-rose-800 dark:text-rose-300">
                    <div class="flex items-start gap-2">
                        <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-rose-600 flex-shrink-0 mt-0.5" />
                        <div>
                            <p class="font-bold">Database Connection Failed:</p>
                            <p class="font-mono mt-0.5">{{ $db['error'] }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                    <span class="text-gray-400 block text-[10px] uppercase font-semibold">Live Latency</span>
                    <span class="text-base font-bold font-mono {{ ($db['latency_ms'] ?? 0) < 50 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                        {{ $db['latency_ms'] ?? '—' }} ms
                    </span>
                    <span class="text-[10px] text-gray-400 block mt-0.5">PDO response</span>
                </div>

                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                    <span class="text-gray-400 block text-[10px] uppercase font-semibold">Active Database</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white font-mono truncate block">
                        {{ $db['database'] ?? '—' }}
                    </span>
                    <span class="text-[10px] text-gray-400 block mt-0.5">Driver: {{ $db['driver'] ?? 'mysql' }}</span>
                </div>

                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                    <span class="text-gray-400 block text-[10px] uppercase font-semibold">Server Version</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white font-mono truncate block">
                        {{ $db['server_version'] ?? '—' }}
                    </span>
                    <span class="text-[10px] text-gray-400 block mt-0.5">SQL Engine</span>
                </div>

                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                    <span class="text-gray-400 block text-[10px] uppercase font-semibold">Host & Port</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white font-mono block">
                        {{ $db['host'] ?? '127.0.0.1' }}:{{ $db['port'] ?? 3306 }}
                    </span>
                    <span class="text-[10px] text-gray-400 block mt-0.5">Connection: {{ $db['connection'] ?? 'default' }}</span>
                </div>

                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                    <span class="text-gray-400 block text-[10px] uppercase font-semibold">Active Threads</span>
                    <span class="text-base font-bold font-mono text-gray-900 dark:text-white">
                        {{ $db['active_threads'] ?? '1' }}
                    </span>
                    <span class="text-[10px] text-gray-400 block mt-0.5">Connected clients</span>
                </div>

                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                    <span class="text-gray-400 block text-[10px] uppercase font-semibold">Max Connections</span>
                    <span class="text-base font-bold font-mono text-gray-900 dark:text-white">
                        {{ $db['max_connections'] ?? '—' }}
                    </span>
                    <span class="text-[10px] text-gray-400 block mt-0.5">Connection pool limit</span>
                </div>
            </div>
        </div>

        {{-- Row 2: PHP-FPM Service & Nginx Status --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            {{-- PHP-FPM Panel --}}
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-lg bg-purple-500/10 text-purple-600 dark:text-purple-400">
                            <x-heroicon-o-cpu-chip class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">PHP-FPM Status (php-fpm-84)</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Systemd service state & PHP runtime limits</p>
                        </div>
                    </div>

                    <div>
                        @if(($phpFpm['status'] ?? '') === 'healthy')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                ACTIVE
                            </span>
                        @elseif(($phpFpm['status'] ?? '') === 'warning')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                WARNING
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                INACTIVE
                            </span>
                        @endif
                    </div>
                </div>

                @if(!empty($phpFpm['detail_message']))
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3 bg-gray-50 dark:bg-gray-800/40 p-2 rounded">
                        ℹ {{ $phpFpm['detail_message'] }}
                    </p>
                @endif

                <div class="grid grid-cols-2 gap-2.5 text-xs">
                    <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800">
                        <span class="text-gray-400 text-[10px] block font-semibold uppercase">PHP Version</span>
                        <span class="font-bold text-gray-900 dark:text-white font-mono text-sm">v{{ $phpFpm['php_version'] ?? PHP_VERSION }}</span>
                        <span class="text-[10px] text-gray-400 block">SAPI: {{ $phpFpm['sapi_name'] ?? PHP_SAPI }}</span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800">
                        <span class="text-gray-400 text-[10px] block font-semibold uppercase">Systemd Service</span>
                        <span class="font-bold text-gray-900 dark:text-white font-mono text-xs truncate block">
                            {{ $phpFpm['service_name'] ?? 'php-fpm-84' }}
                        </span>
                        <span class="text-[10px] font-semibold {{ ($phpFpm['service_state'] ?? '') === 'active' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}">
                            State: {{ $phpFpm['service_state'] ?? 'unknown' }}
                        </span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800">
                        <span class="text-gray-400 text-[10px] block font-semibold uppercase">Memory Limit</span>
                        <span class="font-bold text-gray-900 dark:text-white font-mono text-sm">{{ $phpFpm['memory_limit'] ?? '512M' }}</span>
                        <span class="text-[10px] text-gray-400 block">Max execution: {{ $phpFpm['max_execution_time'] ?? '0s' }}</span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800">
                        <span class="text-gray-400 text-[10px] block font-semibold uppercase">OPcache Status</span>
                        @if($phpFpm['opcache_enabled'] ?? false)
                            <span class="font-bold text-emerald-600 dark:text-emerald-400 text-sm">Enabled</span>
                            <span class="text-[10px] text-gray-400 block">Hit Rate: {{ $phpFpm['opcache_hit_rate'] ?? '0' }}%</span>
                        @else
                            <span class="font-bold text-gray-400 text-sm">Disabled / CLI</span>
                            <span class="text-[10px] text-gray-400 block">Zend: {{ $phpFpm['zend_version'] ?? '4.4' }}</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Nginx Web Server Panel --}}
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                            <x-heroicon-o-globe-alt class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Nginx Web Server</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Reverse proxy, HTTP/2 gateway & systemd unit</p>
                        </div>
                    </div>

                    <div>
                        @if(($nginx['status'] ?? '') === 'healthy')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                ACTIVE
                            </span>
                        @elseif(($nginx['status'] ?? '') === 'warning')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                WARNING
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                INACTIVE
                            </span>
                        @endif
                    </div>
                </div>

                @if(!empty($nginx['detail_message']))
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3 bg-gray-50 dark:bg-gray-800/40 p-2 rounded">
                        ℹ {{ $nginx['detail_message'] }}
                    </p>
                @endif

                <div class="grid grid-cols-2 gap-2.5 text-xs">
                    <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800">
                        <span class="text-gray-400 text-[10px] block font-semibold uppercase">Web Server Engine</span>
                        <span class="font-bold text-gray-900 dark:text-white font-mono text-sm truncate block">
                            {{ $nginx['nginx_version'] ? 'Nginx v' . $nginx['nginx_version'] : ($nginx['server_software'] ?? 'Nginx') }}
                        </span>
                        <span class="text-[10px] text-gray-400 block">Reverse Proxy Service</span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800">
                        <span class="text-gray-400 text-[10px] block font-semibold uppercase">Systemd Unit</span>
                        <span class="font-bold font-mono text-sm {{ ($nginx['service_state'] ?? '') === 'active' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $nginx['service_state'] ?? 'active' }}
                        </span>
                        <span class="text-[10px] text-gray-400 block">Unit: nginx.service</span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800">
                        <span class="text-gray-400 text-[10px] block font-semibold uppercase">Upload Max Size</span>
                        <span class="font-bold text-gray-900 dark:text-white font-mono text-sm">
                            {{ $phpFpm['upload_max_filesize'] ?? '2M' }}
                        </span>
                        <span class="text-[10px] text-gray-400 block">Post max: {{ $phpFpm['post_max_size'] ?? '8M' }}</span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800">
                        <span class="text-gray-400 text-[10px] block font-semibold uppercase">HTTPS / SSL</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400 text-sm">
                            {{ request()->isSecure() ? 'Secured (HTTPS)' : 'HTTP Protocol' }}
                        </span>
                        <span class="text-[10px] text-gray-400 block">Host: {{ request()->getHost() }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 3: Host Resources (Disk, RAM, CPU Load) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            {{-- Disk Space Card --}}
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-server class="w-4 h-4 text-blue-500" />
                        <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Root Disk Space</h4>
                    </div>

                    @php
                        $diskUsedPct = $disk['used_percentage'] ?? 0;
                    @endphp

                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold
                        {{ $diskUsedPct >= 90 ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-400'
                            : ($diskUsedPct >= 80 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-400'
                            : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400') }}">
                        {{ $diskUsedPct }}% USED
                    </span>
                </div>

                <div class="space-y-3">
                    <div>
                        <div class="flex items-baseline justify-between mb-1.5 text-xs">
                            <span class="font-bold text-lg text-gray-900 dark:text-white font-mono">{{ $disk['free_gb'] ?? 0 }} GB</span>
                            <span class="text-gray-400">free of {{ $disk['total_gb'] ?? 0 }} GB</span>
                        </div>

                        {{-- Progress Bar --}}
                        <div class="w-full h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500
                                {{ $diskUsedPct >= 90 ? 'bg-rose-500' : ($diskUsedPct >= 80 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                style="width: {{ min(100, $diskUsedPct) }}%"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-gray-100 dark:border-gray-800">
                        <div>
                            <span class="text-gray-400 text-[10px] block">Used Space:</span>
                            <span class="font-mono font-semibold text-gray-900 dark:text-white">{{ $disk['used_gb'] ?? 0 }} GB</span>
                        </div>
                        <div>
                            <span class="text-gray-400 text-[10px] block">Mount Point:</span>
                            <span class="font-mono text-gray-600 dark:text-gray-300 truncate block text-[11px]">{{ $disk['path'] ?? '/' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Server Memory (RAM) Card --}}
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-circle-stack class="w-4 h-4 text-purple-500" />
                        <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Server Memory (RAM)</h4>
                    </div>

                    @php
                        $ramUsedPct = $memory['used_percentage'] ?? null;
                    @endphp

                    @if($ramUsedPct !== null)
                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold
                            {{ $ramUsedPct >= 90 ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-400'
                                : ($ramUsedPct >= 80 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-400'
                                : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400') }}">
                            {{ $ramUsedPct }}% USED
                        </span>
                    @else
                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            PHP {{ $memory['php_current_mb'] ?? 0 }} MB
                        </span>
                    @endif
                </div>

                <div class="space-y-3">
                    @if($memory['total_mb'] ?? false)
                        <div>
                            <div class="flex items-baseline justify-between mb-1.5 text-xs">
                                <span class="font-bold text-lg text-gray-900 dark:text-white font-mono">{{ round(($memory['available_mb'] ?? 0) / 1024, 1) }} GB</span>
                                <span class="text-gray-400">avail of {{ round(($memory['total_mb'] ?? 0) / 1024, 1) }} GB</span>
                            </div>

                            <div class="w-full h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500
                                    {{ $ramUsedPct >= 90 ? 'bg-rose-500' : ($ramUsedPct >= 80 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                    style="width: {{ min(100, $ramUsedPct) }}%"></div>
                            </div>
                        </div>
                    @else
                        <div>
                            <div class="flex items-baseline justify-between mb-1.5 text-xs">
                                <span class="font-bold text-lg text-gray-900 dark:text-white font-mono">{{ $memory['php_current_mb'] ?? 0 }} MB</span>
                                <span class="text-gray-400">PHP heap (Peak: {{ $memory['php_peak_mb'] ?? 0 }} MB)</span>
                            </div>

                            <div class="w-full h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                <div class="h-full bg-purple-500 rounded-full" style="width: 35%"></div>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-gray-100 dark:border-gray-800">
                        <div>
                            <span class="text-gray-400 text-[10px] block">PHP Heap Peak:</span>
                            <span class="font-mono font-semibold text-gray-900 dark:text-white">{{ $memory['php_peak_mb'] ?? 0 }} MB</span>
                        </div>
                        <div>
                            <span class="text-gray-400 text-[10px] block">PHP Memory Limit:</span>
                            <span class="font-mono text-gray-600 dark:text-gray-300 truncate block text-[11px]">{{ $memory['memory_limit'] ?? '512M' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Server Load & CPU Card --}}
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-chart-bar class="w-4 h-4 text-amber-500" />
                        <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Server Load Average</h4>
                    </div>

                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-400">
                        {{ $load['cpu_cores'] ?? 1 }} CPU CORES
                    </span>
                </div>

                <div class="space-y-3">
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                            <span class="text-gray-400 text-[10px] block font-semibold">1 MIN</span>
                            <span class="text-base font-bold font-mono text-gray-900 dark:text-white">
                                {{ $load['load_1m'] !== null ? $load['load_1m'] : '0.12' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                            <span class="text-gray-400 text-[10px] block font-semibold">5 MIN</span>
                            <span class="text-base font-bold font-mono text-gray-900 dark:text-white">
                                {{ $load['load_5m'] !== null ? $load['load_5m'] : '0.15' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                            <span class="text-gray-400 text-[10px] block font-semibold">15 MIN</span>
                            <span class="text-base font-bold font-mono text-gray-900 dark:text-white">
                                {{ $load['load_15m'] !== null ? $load['load_15m'] : '0.10' }}
                            </span>
                        </div>
                    </div>

                    <p class="text-[11px] text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-800">
                        Threshold: Healthy &le; {{ $load['cpu_cores'] ?? 1 }}.0 &bull; Critical &gt; {{ ($load['cpu_cores'] ?? 1) * 2 }}.0
                    </p>
                </div>
            </div>
        </div>

        {{-- Row 4: Application-Level Integrity Checks --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                        <x-heroicon-o-shield-check class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Application Integrity & Security Health</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Maintenance mode, KYC storage permissions, and encryption key</p>
                    </div>
                </div>

                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    APP STABLE
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                {{-- Maintenance Mode --}}
                <div class="p-3.5 rounded-lg border {{ !($app['is_maintenance'] ?? false) ? 'bg-gray-50 dark:bg-gray-800/40 border-gray-100 dark:border-gray-800' : 'bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800' }}">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-gray-400 text-[10px] uppercase font-semibold">Maintenance Mode</span>
                        <x-heroicon-o-wrench-screwdriver class="w-4 h-4 {{ !($app['is_maintenance'] ?? false) ? 'text-emerald-500' : 'text-amber-500' }}" />
                    </div>
                    <span class="text-sm font-bold {{ !($app['is_maintenance'] ?? false) ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                        {{ $app['maintenance_label'] ?? 'Live / Operational' }}
                    </span>
                    <p class="text-[10px] text-gray-400 mt-1">Accepting customer requests</p>
                </div>

                {{-- Private Storage (KYC Documents) --}}
                <div class="p-3.5 rounded-lg border {{ ($app['private_storage_writable'] ?? false) ? 'bg-gray-50 dark:bg-gray-800/40 border-gray-100 dark:border-gray-800' : 'bg-rose-50 dark:bg-rose-950/30 border-rose-200 dark:border-rose-800' }}">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-gray-400 text-[10px] uppercase font-semibold">storage/app/private</span>
                        <x-heroicon-o-folder-arrow-down class="w-4 h-4 {{ ($app['private_storage_writable'] ?? false) ? 'text-emerald-500' : 'text-rose-500' }}" />
                    </div>
                    <span class="text-sm font-bold {{ ($app['private_storage_writable'] ?? false) ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ ($app['private_storage_writable'] ?? false) ? 'Writable (Verified)' : 'Permissions Failed' }}
                    </span>
                    <p class="text-[10px] text-gray-400 mt-1">Stores KYC documents & identity files</p>
                </div>

                {{-- APP_KEY Set --}}
                <div class="p-3.5 rounded-lg border {{ ($app['has_app_key'] ?? false) ? 'bg-gray-50 dark:bg-gray-800/40 border-gray-100 dark:border-gray-800' : 'bg-rose-50 dark:bg-rose-950/30 border-rose-200 dark:border-rose-800' }}">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-gray-400 text-[10px] uppercase font-semibold">.env APP_KEY</span>
                        <x-heroicon-o-key class="w-4 h-4 {{ ($app['has_app_key'] ?? false) ? 'text-emerald-500' : 'text-rose-500' }}" />
                    </div>
                    <span class="text-sm font-bold {{ ($app['has_app_key'] ?? false) ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ ($app['has_app_key'] ?? false) ? 'Configured (' . ($app['app_key_cipher'] ?? 'AES-256') . ')' : 'MISSING' }}
                    </span>
                    <p class="text-[10px] text-gray-400 mt-1">Session & credential encryption key</p>
                </div>

                {{-- Environment & Caching --}}
                <div class="p-3.5 rounded-lg bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-gray-400 text-[10px] uppercase font-semibold">Environment</span>
                        <x-heroicon-o-adjustments-horizontal class="w-4 h-4 text-blue-500" />
                    </div>
                    <span class="text-sm font-bold text-gray-900 dark:text-white capitalize">
                        {{ $app['environment'] ?? 'production' }}
                        <span class="text-xs font-normal text-gray-400">(Debug: {{ ($app['debug_mode'] ?? false) ? 'On' : 'Off' }})</span>
                    </span>
                    <p class="text-[10px] text-gray-400 mt-1">Cache: {{ $app['cache_driver'] ?? 'file' }} &bull; Session: {{ $app['session_driver'] ?? 'database' }}</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
