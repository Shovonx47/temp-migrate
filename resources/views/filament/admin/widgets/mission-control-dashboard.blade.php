@php
    $data = $this->getDashboardData();
    $kpis = $data['kpis'];
    $qc = $data['queue_chart'];
    $tps = $data['tps_chart'];
    $gateways = $data['gateways'];
    $topCustomers = $data['top_customers'];
    $health = $data['health'];
    $alerts = $data['alerts'];
    $sysTime = $data['system_time'];

    $db = $health['database'] ?? [];
    $phpFpm = $health['php_fpm'] ?? [];
    $nginx = $health['nginx'] ?? [];
    $disk = $health['disk'] ?? [];
    $load = $health['load'] ?? [];
    $memory = $health['memory'] ?? [];

    $totalQ = (int) str_replace(',', '', $kpis['total_queue']);
    $pendingQ = (int) str_replace(',', '', $kpis['pending_queue']);
    $processingQ = (int) str_replace(',', '', $kpis['processing']);
    $quickQ = (int) ($kpis['quick_queue'] ?? 0);
    $bulkQ = (int) ($kpis['bulk_queue'] ?? 0);
    $apiQ = (int) ($kpis['api_queue'] ?? 0);
    $webQ = (int) ($kpis['web_queue'] ?? 0);

    $quickPct = $totalQ > 0 ? round(($quickQ / $totalQ) * 100, 1) : 0;
    $bulkPct = $totalQ > 0 ? round(($bulkQ / $totalQ) * 100, 1) : 0;
    $pendingPct = $totalQ > 0 ? round(($pendingQ / $totalQ) * 100, 1) : 0;
    $processingPct = $totalQ > 0 ? round(($processingQ / $totalQ) * 100, 1) : 0;
@endphp

<div wire:poll.5s class="w-full text-gray-900 dark:text-white antialiased font-sans">
    {{-- Application Native Card Container --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4 lg:p-6 space-y-5">

        {{-- 1. Top Bar: System Time & Live Indicator --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">System Time:</span>
                    <span class="text-xs font-mono font-bold text-gray-900 dark:text-white">{{ $sysTime }}</span>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    LIVE DASHBOARD
                </span>
            </div>

            <div class="flex items-center gap-3">
                {{-- Auto Refresh toggle button --}}
                <button
                    type="button"
                    wire:click="togglePause"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-50 hover:bg-gray-100 border border-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 text-xs font-medium text-gray-700 dark:text-gray-300 transition-colors cursor-pointer"
                >
                    <x-heroicon-o-arrow-path class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400 {{ $isPaused ? '' : 'animate-spin' }}" />
                    <span>Auto Refresh (5s)</span>
                    <span class="text-[10px] text-gray-400">{{ $isPaused ? 'Paused' : 'Active' }}</span>
                </button>

                <div class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 text-xs font-medium">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>All Engines Stable</span>
                </div>
            </div>
        </div>

        {{-- 2. Top 8 KPI Metric Cards (100% Real Live Dynamic Data) --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
            {{-- Card 1: Live In-Queue Total --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 p-3.5 flex flex-col justify-between min-h-[95px] hover:shadow-sm transition-shadow">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">IN-QUEUE NOW</span>
                    <div class="w-6 h-6 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <x-heroicon-o-queue-list class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div class="my-1">
                    <span class="text-base lg:text-lg font-extrabold text-gray-900 dark:text-white font-mono tracking-tight">{{ $kpis['total_queue'] }}</span>
                </div>
                <div class="text-[10px] text-blue-600 dark:text-blue-400 font-medium flex items-center gap-1">
                    <span>Live Pipeline</span>
                </div>
            </div>

            {{-- Card 2: Pending Queue --}}
            <div class="rounded-xl border border-amber-200 dark:border-amber-900/40 bg-amber-50/30 dark:bg-amber-950/20 p-3.5 flex flex-col justify-between min-h-[95px] hover:shadow-sm transition-shadow">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">PENDING QUEUE</span>
                    <div class="w-6 h-6 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <x-heroicon-o-clock class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div class="my-1">
                    <span class="text-base lg:text-lg font-extrabold text-amber-900 dark:text-amber-300 font-mono tracking-tight">{{ $kpis['pending_queue'] }}</span>
                </div>
                <div class="text-[10px] text-amber-700 dark:text-amber-400 font-medium flex items-center gap-1">
                    <span>On verge of send</span>
                </div>
            </div>

            {{-- Card 3: In-Flight Processing --}}
            <div class="rounded-xl border border-emerald-200 dark:border-emerald-900/40 bg-emerald-50/30 dark:bg-emerald-950/20 p-3.5 flex flex-col justify-between min-h-[95px] hover:shadow-sm transition-shadow">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">IN-FLIGHT</span>
                    <div class="w-6 h-6 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <x-heroicon-o-cog-6-tooth class="w-3.5 h-3.5 {{ $processingQ > 0 ? 'animate-spin' : '' }}" />
                    </div>
                </div>
                <div class="my-1">
                    <span class="text-base lg:text-lg font-extrabold text-emerald-900 dark:text-emerald-300 font-mono tracking-tight">{{ $kpis['processing'] }}</span>
                </div>
                <div class="text-[10px] text-emerald-700 dark:text-emerald-400 font-medium flex items-center gap-1">
                    <span>Transmitting</span>
                </div>
            </div>

            {{-- Card 4: Dispatched (15m) --}}
            <div class="rounded-xl border border-purple-200 dark:border-purple-900/40 bg-purple-50/30 dark:bg-purple-950/20 p-3.5 flex flex-col justify-between min-h-[95px] hover:shadow-sm transition-shadow">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-purple-700 dark:text-purple-400 uppercase tracking-wider">SENT (15M)</span>
                    <div class="w-6 h-6 rounded-full bg-purple-500/15 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                        <x-heroicon-o-paper-airplane class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div class="my-1">
                    <span class="text-base lg:text-lg font-extrabold text-purple-900 dark:text-purple-300 font-mono tracking-tight">{{ $kpis['recent_sent'] }}</span>
                </div>
                <div class="text-[10px] text-purple-700 dark:text-purple-400 font-medium flex items-center gap-1">
                    <span>Recent Dispatched</span>
                </div>
            </div>

            {{-- Card 5: Failed (15m) --}}
            <div class="rounded-xl border border-rose-200 dark:border-rose-900/40 bg-rose-50/30 dark:bg-rose-950/20 p-3.5 flex flex-col justify-between min-h-[95px] hover:shadow-sm transition-shadow">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-rose-700 dark:text-rose-400 uppercase tracking-wider">FAILED (15M)</span>
                    <div class="w-6 h-6 rounded-full bg-rose-500/15 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <x-heroicon-o-x-circle class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div class="my-1">
                    <span class="text-base lg:text-lg font-extrabold text-rose-900 dark:text-rose-300 font-mono tracking-tight">{{ $kpis['recent_failed'] }}</span>
                </div>
                <div class="text-[10px] text-rose-700 dark:text-rose-400 font-medium flex items-center gap-1">
                    <span>Recent Errors</span>
                </div>
            </div>

            {{-- Card 6: Live Success Rate --}}
            <div class="rounded-xl border border-cyan-200 dark:border-cyan-900/40 bg-cyan-50/30 dark:bg-cyan-950/20 p-3.5 flex flex-col justify-between min-h-[95px] hover:shadow-sm transition-shadow">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-cyan-700 dark:text-cyan-400 uppercase tracking-wider">SUCCESS RATE</span>
                    <div class="w-6 h-6 rounded-full bg-cyan-500/15 text-cyan-600 dark:text-cyan-400 flex items-center justify-center">
                        <x-heroicon-o-check-circle class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div class="my-1">
                    <span class="text-base lg:text-lg font-extrabold text-cyan-900 dark:text-cyan-300 font-mono tracking-tight">{{ $kpis['dlr_success'] }}%</span>
                </div>
                <div class="text-[10px] text-cyan-700 dark:text-cyan-400 font-medium flex items-center gap-1">
                    <span>Live Health</span>
                </div>
            </div>

            {{-- Card 7: Current TPS --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 p-3.5 flex flex-col justify-between min-h-[95px] hover:shadow-sm transition-shadow">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">CURRENT TPS</span>
                    <div class="w-6 h-6 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <x-heroicon-o-bolt class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div class="my-1">
                    <span class="text-base lg:text-lg font-extrabold text-gray-900 dark:text-white font-mono tracking-tight">{{ $kpis['current_tps'] }} <span class="text-[10px] text-gray-400 font-normal">SMS/s</span></span>
                </div>
                <div class="text-[10px] text-amber-700 dark:text-amber-400 font-medium flex items-center gap-1">
                    <span>Real-time Speed</span>
                </div>
            </div>

            {{-- Card 8: System Latency --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 p-3.5 flex flex-col justify-between min-h-[95px] hover:shadow-sm transition-shadow">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider">DB LATENCY</span>
                    <div class="w-6 h-6 rounded-full bg-blue-500/15 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <x-heroicon-o-cpu-chip class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div class="my-1">
                    <span class="text-base lg:text-lg font-extrabold text-gray-900 dark:text-white font-mono tracking-tight">{{ $kpis['db_latency'] }} <span class="text-[10px] text-gray-400 font-normal">ms</span></span>
                </div>
                <div class="text-[10px] text-blue-700 dark:text-blue-400 font-medium flex items-center gap-1">
                    <span>Engine Response</span>
                </div>
            </div>
        </div>

        {{-- 3. Middle Row 1: Real-Time Queue Overview Chart + TPS Chart + Gateway Status --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            {{-- Queue Overview Dynamic Line Chart (5 cols) --}}
            <div class="lg:col-span-5 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 flex flex-col justify-between shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">REAL-TIME QUEUE OVERVIEW</h3>
                        {{-- Legend --}}
                        <div class="flex items-center gap-3 mt-1.5 text-[10px]">
                            <span class="flex items-center gap-1 text-gray-600 dark:text-gray-300"><span class="w-2.5 h-1 rounded-full bg-amber-500"></span> Pending</span>
                            <span class="flex items-center gap-1 text-gray-600 dark:text-gray-300"><span class="w-2.5 h-1 rounded-full bg-blue-500"></span> Sending</span>
                            <span class="flex items-center gap-1 text-gray-600 dark:text-gray-300"><span class="w-2.5 h-1 rounded-full bg-emerald-500"></span> Sent</span>
                            <span class="flex items-center gap-1 text-gray-600 dark:text-gray-300"><span class="w-2.5 h-1 rounded-full bg-rose-500"></span> Failed</span>
                        </div>
                    </div>
                    <span class="text-[10px] px-2 py-1 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 font-mono border border-gray-200 dark:border-gray-700">Last 15 Minutes &darr;</span>
                </div>

                {{-- Dynamic SVG Chart from DB Data --}}
                <div class="w-full h-44 relative mt-2">
                    <svg class="w-full h-full" viewBox="0 0 500 160" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="liveSentGlow" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#10b981" stop-opacity="0.25"/>
                                <stop offset="100%" stop-color="#10b981" stop-opacity="0"/>
                            </linearGradient>
                        </defs>
                        {{-- Grid lines --}}
                        <line x1="40" y1="20" x2="500" y2="20" stroke="#e2e8f0" stroke-dasharray="3,3" class="dark:stroke-gray-800" />
                        <line x1="40" y1="60" x2="500" y2="60" stroke="#e2e8f0" stroke-dasharray="3,3" class="dark:stroke-gray-800" />
                        <line x1="40" y1="100" x2="500" y2="100" stroke="#e2e8f0" stroke-dasharray="3,3" class="dark:stroke-gray-800" />
                        <line x1="40" y1="140" x2="500" y2="140" stroke="#cbd5e1" class="dark:stroke-gray-700" />

                        {{-- Y Axis labels dynamically scaled --}}
                        <text x="5" y="24" fill="#94a3b8" font-size="9" font-family="monospace">{{ $qc['max_val'] }}</text>
                        <text x="5" y="64" fill="#94a3b8" font-size="9" font-family="monospace">{{ (int) ($qc['max_val'] * 0.66) }}</text>
                        <text x="5" y="104" fill="#94a3b8" font-size="9" font-family="monospace">{{ (int) ($qc['max_val'] * 0.33) }}</text>
                        <text x="25" y="144" fill="#94a3b8" font-size="9" font-family="monospace">0</text>

                        {{-- Sent Area & Line (Green) --}}
                        <path d="{{ $qc['sent_area'] }}" fill="url(#liveSentGlow)" />
                        <polyline points="{{ $qc['sent_line'] }}" fill="none" stroke="#10b981" stroke-width="2.5" />

                        {{-- Pending Line (Amber) --}}
                        <polyline points="{{ $qc['pending_line'] }}" fill="none" stroke="#f59e0b" stroke-width="2" />

                        {{-- Processing Line (Blue) --}}
                        <polyline points="{{ $qc['processing_line'] }}" fill="none" stroke="#3b82f6" stroke-width="2" />

                        {{-- Failed Line (Red) --}}
                        <polyline points="{{ $qc['failed_line'] }}" fill="none" stroke="#ef4444" stroke-width="1.5" />
                    </svg>

                    {{-- X Axis Time labels --}}
                    <div class="flex justify-between text-[9px] text-gray-400 font-mono mt-1 pl-8">
                        @foreach($qc['labels'] ?? [] as $index => $lbl)
                            @if($index % 3 === 0)
                                <span>{{ $lbl }}</span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- TPS (SMS Per Second) Area Spline Chart (4 cols) --}}
            <div class="lg:col-span-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 flex flex-col justify-between shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">TPS (SMS PER SECOND)</h3>
                    <span class="text-[10px] px-2 py-1 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 font-mono border border-gray-200 dark:border-gray-700">Last 15 Minutes &darr;</span>
                </div>

                <div class="w-full h-44 relative mt-2">
                    <svg class="w-full h-full" viewBox="0 0 500 160" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="liveTpsBlueGlow" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#0284c7" stop-opacity="0.3"/>
                                <stop offset="100%" stop-color="#0284c7" stop-opacity="0.02"/>
                            </linearGradient>
                        </defs>
                        <line x1="30" y1="20" x2="500" y2="20" stroke="#e2e8f0" stroke-dasharray="3,3" class="dark:stroke-gray-800" />
                        <line x1="30" y1="60" x2="500" y2="60" stroke="#e2e8f0" stroke-dasharray="3,3" class="dark:stroke-gray-800" />
                        <line x1="30" y1="100" x2="500" y2="100" stroke="#e2e8f0" stroke-dasharray="3,3" class="dark:stroke-gray-800" />
                        <line x1="30" y1="140" x2="500" y2="140" stroke="#cbd5e1" class="dark:stroke-gray-700" />

                        <text x="5" y="24" fill="#94a3b8" font-size="9" font-family="monospace">{{ $tps['max_val'] }}</text>
                        <text x="5" y="64" fill="#94a3b8" font-size="9" font-family="monospace">{{ round($tps['max_val'] * 0.66, 1) }}</text>
                        <text x="5" y="104" fill="#94a3b8" font-size="9" font-family="monospace">{{ round($tps['max_val'] * 0.33, 1) }}</text>
                        <text x="15" y="144" fill="#94a3b8" font-size="9" font-family="monospace">0.0</text>

                        {{-- Area Fill --}}
                        <path d="{{ $tps['tps_area'] }}" fill="url(#liveTpsBlueGlow)" />
                        {{-- Spline Line --}}
                        <polyline points="{{ $tps['tps_line'] }}" fill="none" stroke="#0284c7" stroke-width="2.5" />
                    </svg>

                    <div class="flex justify-between text-[9px] text-gray-400 font-mono mt-1 pl-6">
                        @foreach($tps['labels'] ?? [] as $index => $lbl)
                            @if($index % 3 === 0)
                                <span>{{ $lbl }}</span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Gateway Status Table (3 cols) --}}
            <div class="lg:col-span-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 flex flex-col justify-between shadow-xs">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">GATEWAY STATUS</h3>
                    <a href="{{ url('/superman/settings/gateways') }}" class="text-[10px] text-primary-600 hover:text-primary-700 dark:text-primary-400 font-semibold">View All</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-800 text-gray-400 text-[10px] uppercase">
                                <th class="pb-2 font-semibold">Gateway</th>
                                <th class="pb-2 font-semibold">Status</th>
                                <th class="pb-2 font-semibold text-center">TPS</th>
                                <th class="pb-2 font-semibold text-right">Success %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($gateways as $gw)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition-colors">
                                    <td class="py-2 font-medium text-gray-900 dark:text-white text-xs truncate max-w-[90px]" title="{{ $gw['name'] }}">
                                        {{ $gw['name'] }}
                                    </td>
                                    <td class="py-2">
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold
                                            {{ $gw['status'] === 'Online' ? 'text-emerald-600 dark:text-emerald-400' : ($gw['status'] === 'Degraded' ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $gw['status'] === 'Online' ? 'bg-emerald-500' : ($gw['status'] === 'Degraded' ? 'bg-amber-500' : 'bg-rose-500') }}"></span>
                                            {{ $gw['status'] }}
                                        </span>
                                    </td>
                                    <td class="py-2 text-center font-mono text-gray-700 dark:text-gray-300 text-xs">{{ $gw['tps'] }}</td>
                                    <td class="py-2 text-right font-mono font-semibold text-xs {{ $gw['success_rate'] >= 98 ? 'text-emerald-600 dark:text-emerald-400' : ($gw['success_rate'] >= 90 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">
                                        {{ $gw['success_rate'] }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-gray-400 text-xs">No gateways configured</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- 4. Middle Row 2: Live Queue Breakdown, Top Customers in Queue, Routing Channel & Alerts --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Queue by Type (Quick SMS vs Bulk Batch) --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 flex flex-col justify-between shadow-xs">
                <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-2">QUEUE BY TYPE</h3>
                <div class="flex items-center justify-center relative my-2">
                    <svg class="w-28 h-28 transform -rotate-90" viewBox="0 0 36 36">
                        <path class="text-gray-100 dark:text-gray-800" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        @if($totalQ > 0)
                            <path class="text-amber-500" stroke-dasharray="{{ $quickPct }}, 100" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="text-purple-500" stroke-dasharray="{{ $bulkPct }}, 100" stroke-dashoffset="-{{ $quickPct }}" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        @endif
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span class="text-[9px] text-gray-400">Total</span>
                        <span class="text-xs font-bold font-mono text-gray-900 dark:text-white">{{ $kpis['total_queue'] }}</span>
                    </div>
                </div>
                {{-- Legend --}}
                <div class="space-y-1.5 text-[11px] pt-2 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Quick SMS</span>
                        <span class="font-mono text-gray-500 dark:text-gray-400">{{ number_format($quickQ) }} ({{ $quickPct }}%)</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="w-2 h-2 rounded-full bg-purple-500"></span> Bulk Batch</span>
                        <span class="font-mono text-gray-500 dark:text-gray-400">{{ number_format($bulkQ) }} ({{ $bulkPct }}%)</span>
                    </div>
                </div>
            </div>

            {{-- Queue by Customer (Top Active in Queue) --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 flex flex-col justify-between shadow-xs">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">ACTIVE CUSTOMERS IN QUEUE</h3>
                    <a href="{{ url('/superman/users/customers') }}" class="text-[10px] text-primary-600 hover:text-primary-700 dark:text-primary-400 font-semibold">View All</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-[11px]">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-800 text-gray-400 text-[10px] uppercase">
                                <th class="pb-1.5 font-semibold">Customer</th>
                                <th class="pb-1.5 font-semibold text-amber-600 dark:text-amber-400">Pending</th>
                                <th class="pb-1.5 font-semibold text-blue-600 dark:text-blue-400">Sending</th>
                                <th class="pb-1.5 font-semibold text-emerald-600 dark:text-emerald-400 text-right">Sent (15m)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($topCustomers as $tc)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition-colors">
                                    <td class="py-1.5 font-medium text-gray-900 dark:text-white truncate max-w-[80px]">{{ $tc['name'] }}</td>
                                    <td class="py-1.5 font-mono text-amber-700 dark:text-amber-300 font-medium">{{ number_format($tc['pending']) }}</td>
                                    <td class="py-1.5 font-mono text-blue-700 dark:text-blue-300 font-medium">{{ number_format($tc['processing']) }}</td>
                                    <td class="py-1.5 font-mono text-emerald-700 dark:text-emerald-300 text-right font-medium">{{ number_format($tc['sent']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-gray-400 text-xs">
                                        No customer messages waiting in queue
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Queue by Status Breakdown --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 flex flex-col justify-between shadow-xs">
                <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-2">LIVE PIPELINE STATUS</h3>
                <div class="flex items-center justify-center relative my-2">
                    <svg class="w-28 h-28 transform -rotate-90" viewBox="0 0 36 36">
                        <path class="text-gray-100 dark:text-gray-800" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        @if($totalQ > 0)
                            <path class="text-amber-500" stroke-dasharray="{{ $pendingPct }}, 100" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="text-blue-500" stroke-dasharray="{{ $processingPct }}, 100" stroke-dashoffset="-{{ $pendingPct }}" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        @endif
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span class="text-[9px] text-gray-400">In Pipeline</span>
                        <span class="text-xs font-bold font-mono text-gray-900 dark:text-white">{{ $kpis['total_queue'] }}</span>
                    </div>
                </div>

                <div class="space-y-1 text-[10px] pt-2 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1 text-gray-600 dark:text-gray-300"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Pending in Queue</span>
                        <span class="font-mono text-gray-500 dark:text-gray-400">{{ $kpis['pending_queue'] }} ({{ $pendingPct }}%)</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1 text-gray-600 dark:text-gray-300"><span class="w-2 h-2 rounded-full bg-blue-500"></span> Transmitting Now</span>
                        <span class="font-mono text-gray-500 dark:text-gray-400">{{ $kpis['processing'] }} ({{ $processingPct }}%)</span>
                    </div>
                </div>
            </div>

            {{-- Source Channel & Real-time Alerts --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 flex flex-col justify-between shadow-xs">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">LIVE INGESTION & ALERTS</h3>
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                </div>

                <div class="space-y-2 my-1">
                    {{-- Channel Ingestion Distribution --}}
                    <div class="p-2 rounded bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                        <div class="flex items-center justify-between text-[11px] mb-1">
                            <span class="text-gray-500 dark:text-gray-400">Queue Source:</span>
                            <span class="font-mono font-bold text-gray-900 dark:text-white">API: {{ $apiQ }} | Web: {{ $webQ }}</span>
                        </div>
                        <div class="w-full h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden flex">
                            @php
                                $totalSrc = $apiQ + $webQ;
                                $apiSrcPct = $totalSrc > 0 ? ($apiQ / $totalSrc) * 100 : 50;
                            @endphp
                            <div style="width: {{ $apiSrcPct }}%" class="bg-indigo-500 h-full"></div>
                            <div style="width: {{ 100 - $apiSrcPct }}%" class="bg-blue-400 h-full"></div>
                        </div>
                    </div>

                    {{-- Live System Status Alert --}}
                    @foreach($alerts as $alert)
                        <div class="p-2 rounded bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50 flex items-start gap-2">
                            <x-heroicon-o-check-circle class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0 mt-0.5" />
                            <div class="min-w-0 flex-1 text-[10px]">
                                <p class="text-emerald-900 dark:text-emerald-200 font-medium leading-tight">{{ $alert['title'] }}</p>
                                <span class="text-emerald-600 dark:text-emerald-400 font-mono text-[9px]">{{ $alert['time'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400">
                    <span>5s Live Engine Polling</span>
                    <span class="font-mono text-emerald-600 dark:text-emerald-400 font-semibold">Active</span>
                </div>
            </div>
        </div>

    </div>
</div>
