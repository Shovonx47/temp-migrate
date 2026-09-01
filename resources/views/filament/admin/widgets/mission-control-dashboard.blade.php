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
                    LIVE
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

        {{-- 2. Top 8 KPI Metric Cards (Clean Application Theme) --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
            {{-- Card 1: Total SMS --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 p-3.5 flex flex-col justify-between min-h-[95px] hover:shadow-sm transition-shadow">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">TOTAL SMS</span>
                    <div class="w-6 h-6 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <x-heroicon-o-chat-bubble-left-right class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div class="my-1">
                    <span class="text-base lg:text-lg font-extrabold text-gray-900 dark:text-white font-mono tracking-tight">{{ $kpis['total_sms'] }}</span>
                </div>
                <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1">
                    <span>Today &uarr; 18.5%</span>
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
                    <span>&uarr; 8.3%</span>
                </div>
            </div>

            {{-- Card 3: Processing --}}
            <div class="rounded-xl border border-emerald-200 dark:border-emerald-900/40 bg-emerald-50/30 dark:bg-emerald-950/20 p-3.5 flex flex-col justify-between min-h-[95px] hover:shadow-sm transition-shadow">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">PROCESSING</span>
                    <div class="w-6 h-6 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <x-heroicon-o-cog-6-tooth class="w-3.5 h-3.5 animate-spin" />
                    </div>
                </div>
                <div class="my-1">
                    <span class="text-base lg:text-lg font-extrabold text-emerald-900 dark:text-emerald-300 font-mono tracking-tight">{{ $kpis['processing'] }}</span>
                </div>
                <div class="text-[10px] text-emerald-700 dark:text-emerald-400 font-medium flex items-center gap-1">
                    <span>&uarr; 5.2%</span>
                </div>
            </div>

            {{-- Card 4: Sent --}}
            <div class="rounded-xl border border-purple-200 dark:border-purple-900/40 bg-purple-50/30 dark:bg-purple-950/20 p-3.5 flex flex-col justify-between min-h-[95px] hover:shadow-sm transition-shadow">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-purple-700 dark:text-purple-400 uppercase tracking-wider">SENT</span>
                    <div class="w-6 h-6 rounded-full bg-purple-500/15 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                        <x-heroicon-o-paper-airplane class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div class="my-1">
                    <span class="text-base lg:text-lg font-extrabold text-purple-900 dark:text-purple-300 font-mono tracking-tight">{{ $kpis['sent'] }}</span>
                </div>
                <div class="text-[10px] text-purple-700 dark:text-purple-400 font-medium flex items-center gap-1">
                    <span>&uarr; 20.1%</span>
                </div>
            </div>

            {{-- Card 5: Failed --}}
            <div class="rounded-xl border border-rose-200 dark:border-rose-900/40 bg-rose-50/30 dark:bg-rose-950/20 p-3.5 flex flex-col justify-between min-h-[95px] hover:shadow-sm transition-shadow">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-rose-700 dark:text-rose-400 uppercase tracking-wider">FAILED</span>
                    <div class="w-6 h-6 rounded-full bg-rose-500/15 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <x-heroicon-o-x-circle class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div class="my-1">
                    <span class="text-base lg:text-lg font-extrabold text-rose-900 dark:text-rose-300 font-mono tracking-tight">{{ $kpis['failed'] }}</span>
                </div>
                <div class="text-[10px] text-rose-700 dark:text-rose-400 font-medium flex items-center gap-1">
                    <span>&darr; 2.7%</span>
                </div>
            </div>

            {{-- Card 6: DLR Success --}}
            <div class="rounded-xl border border-cyan-200 dark:border-cyan-900/40 bg-cyan-50/30 dark:bg-cyan-950/20 p-3.5 flex flex-col justify-between min-h-[95px] hover:shadow-sm transition-shadow">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-cyan-700 dark:text-cyan-400 uppercase tracking-wider">DLR SUCCESS</span>
                    <div class="w-6 h-6 rounded-full bg-cyan-500/15 text-cyan-600 dark:text-cyan-400 flex items-center justify-center">
                        <x-heroicon-o-check-circle class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div class="my-1">
                    <span class="text-base lg:text-lg font-extrabold text-cyan-900 dark:text-cyan-300 font-mono tracking-tight">{{ $kpis['dlr_success'] }}%</span>
                </div>
                <div class="text-[10px] text-cyan-700 dark:text-cyan-400 font-medium flex items-center gap-1">
                    <span>&uarr; 1.4%</span>
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
                    <span>&uarr; 12.3%</span>
                </div>
            </div>

            {{-- Card 8: Queue Delay --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 p-3.5 flex flex-col justify-between min-h-[95px] hover:shadow-sm transition-shadow">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider">QUEUE DELAY</span>
                    <div class="w-6 h-6 rounded-full bg-blue-500/15 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <x-heroicon-o-clock class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div class="my-1">
                    <span class="text-base lg:text-lg font-extrabold text-gray-900 dark:text-white font-mono tracking-tight">{{ $kpis['queue_delay'] }} <span class="text-[10px] text-gray-400 font-normal">sec</span></span>
                </div>
                <div class="text-[10px] text-blue-700 dark:text-blue-400 font-medium flex items-center gap-1">
                    <span>&darr; 0.6 sec</span>
                </div>
            </div>
        </div>

        {{-- 3. Middle Row 1: Real-Time Queue Overview Chart + TPS Chart + Gateway Status --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            {{-- Queue Overview Line Chart (5 cols) --}}
            <div class="lg:col-span-5 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 flex flex-col justify-between shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">REAL-TIME QUEUE OVERVIEW</h3>
                        {{-- Legend --}}
                        <div class="flex items-center gap-3 mt-1.5 text-[10px]">
                            <span class="flex items-center gap-1 text-gray-600 dark:text-gray-300"><span class="w-2.5 h-1 rounded-full bg-amber-500"></span> Pending</span>
                            <span class="flex items-center gap-1 text-gray-600 dark:text-gray-300"><span class="w-2.5 h-1 rounded-full bg-blue-500"></span> Processing</span>
                            <span class="flex items-center gap-1 text-gray-600 dark:text-gray-300"><span class="w-2.5 h-1 rounded-full bg-emerald-500"></span> Sent</span>
                            <span class="flex items-center gap-1 text-gray-600 dark:text-gray-300"><span class="w-2.5 h-1 rounded-full bg-rose-500"></span> Failed</span>
                        </div>
                    </div>
                    <span class="text-[10px] px-2 py-1 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 font-mono border border-gray-200 dark:border-gray-700">Last 15 Minutes &darr;</span>
                </div>

                {{-- SVG Wave Chart --}}
                <div class="w-full h-44 relative mt-2">
                    <svg class="w-full h-full" viewBox="0 0 500 160" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="sentGlowLight" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#10b981" stop-opacity="0.2"/>
                                <stop offset="100%" stop-color="#10b981" stop-opacity="0"/>
                            </linearGradient>
                        </defs>
                        {{-- Grid lines --}}
                        <line x1="40" y1="20" x2="500" y2="20" stroke="#e2e8f0" stroke-dasharray="3,3" class="dark:stroke-gray-800" />
                        <line x1="40" y1="60" x2="500" y2="60" stroke="#e2e8f0" stroke-dasharray="3,3" class="dark:stroke-gray-800" />
                        <line x1="40" y1="100" x2="500" y2="100" stroke="#e2e8f0" stroke-dasharray="3,3" class="dark:stroke-gray-800" />
                        <line x1="40" y1="140" x2="500" y2="140" stroke="#cbd5e1" class="dark:stroke-gray-700" />

                        {{-- Y Axis labels --}}
                        <text x="5" y="24" fill="#94a3b8" font-size="9" font-family="monospace">200K</text>
                        <text x="5" y="64" fill="#94a3b8" font-size="9" font-family="monospace">150K</text>
                        <text x="5" y="104" fill="#94a3b8" font-size="9" font-family="monospace">50K</text>
                        <text x="25" y="144" fill="#94a3b8" font-size="9" font-family="monospace">0</text>

                        {{-- Sent Line (Green) --}}
                        <path d="M 40 45 Q 80 35, 120 40 T 200 35 T 280 45 T 360 38 T 440 42 T 500 36 L 500 140 L 40 140 Z" fill="url(#sentGlowLight)" />
                        <path d="M 40 45 Q 80 35, 120 40 T 200 35 T 280 45 T 360 38 T 440 42 T 500 36" fill="none" stroke="#10b981" stroke-width="2.5" />

                        {{-- Pending Line (Amber) --}}
                        <path d="M 40 75 Q 80 65, 120 70 T 200 68 T 280 75 T 360 70 T 440 73 T 500 68" fill="none" stroke="#f59e0b" stroke-width="2" />

                        {{-- Processing Line (Blue) --}}
                        <path d="M 40 120 Q 80 122, 120 118 T 200 121 T 280 119 T 360 122 T 440 120 T 500 118" fill="none" stroke="#3b82f6" stroke-width="2" />

                        {{-- Failed Line (Red) --}}
                        <path d="M 40 135 Q 80 136, 120 134 T 200 135 T 280 133 T 360 135 T 440 134 T 500 133" fill="none" stroke="#ef4444" stroke-width="1.5" />

                        {{-- Data points on green sent line --}}
                        @foreach([40, 80, 120, 160, 200, 240, 280, 320, 360, 400, 440, 480] as $cx)
                            <circle cx="{{ $cx }}" cy="{{ 38 + sin($cx) * 5 }}" r="3" fill="#10b981" stroke="#ffffff" stroke-width="1.5" />
                        @endforeach
                    </svg>

                    {{-- X Axis Time labels --}}
                    <div class="flex justify-between text-[9px] text-gray-400 font-mono mt-1 pl-8">
                        @foreach($qc['labels'] ?? [] as $index => $lbl)
                            @if($index % 2 === 0)
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
                    <svg class="w-full h-full" viewBox="0 0 400 160" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="tpsBlueGlowLight" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#0284c7" stop-opacity="0.3"/>
                                <stop offset="100%" stop-color="#0284c7" stop-opacity="0.02"/>
                            </linearGradient>
                        </defs>
                        <line x1="30" y1="20" x2="400" y2="20" stroke="#e2e8f0" stroke-dasharray="3,3" class="dark:stroke-gray-800" />
                        <line x1="30" y1="60" x2="400" y2="60" stroke="#e2e8f0" stroke-dasharray="3,3" class="dark:stroke-gray-800" />
                        <line x1="30" y1="100" x2="400" y2="100" stroke="#e2e8f0" stroke-dasharray="3,3" class="dark:stroke-gray-800" />
                        <line x1="30" y1="140" x2="400" y2="140" stroke="#cbd5e1" class="dark:stroke-gray-700" />

                        <text x="5" y="24" fill="#94a3b8" font-size="9" font-family="monospace">250</text>
                        <text x="5" y="64" fill="#94a3b8" font-size="9" font-family="monospace">150</text>
                        <text x="5" y="104" fill="#94a3b8" font-size="9" font-family="monospace">50</text>
                        <text x="15" y="144" fill="#94a3b8" font-size="9" font-family="monospace">0</text>

                        {{-- Area Spline Fill --}}
                        <path d="M 30 50 Q 70 35, 110 45 T 180 35 T 250 48 T 320 38 T 400 42 L 400 140 L 30 140 Z" fill="url(#tpsBlueGlowLight)" />
                        {{-- Spline Line --}}
                        <path d="M 30 50 Q 70 35, 110 45 T 180 35 T 250 48 T 320 38 T 400 42" fill="none" stroke="#0284c7" stroke-width="2.5" />

                        @foreach([30, 75, 120, 165, 210, 255, 300, 345, 390] as $cx)
                            <circle cx="{{ $cx }}" cy="{{ 42 + sin($cx * 0.1) * 7 }}" r="3" fill="#0284c7" stroke="#ffffff" stroke-width="1.5" />
                        @endforeach
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
                                    <td class="py-2 font-medium text-gray-900 dark:text-white text-xs truncate max-w-[90px]">{{ $gw['name'] }}</td>
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
                                    <td colspan="4" class="py-4 text-center text-gray-400 text-xs">No gateways found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- 4. Middle Row 2: Queue by Priority (Donut), Top 5 Customer Table, Queue by Status (Donut), Worker Status (Donut) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Queue by Priority --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 flex flex-col justify-between shadow-xs">
                <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-2">QUEUE BY PRIORITY</h3>
                <div class="flex items-center justify-center relative my-2">
                    {{-- Donut SVG --}}
                    <svg class="w-28 h-28 transform -rotate-90" viewBox="0 0 36 36">
                        <path class="text-gray-100 dark:text-gray-800" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        {{-- High Priority (Red 10%) --}}
                        <path class="text-rose-500" stroke-dasharray="10, 100" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        {{-- Normal Priority (Blue 75%) --}}
                        <path class="text-blue-500" stroke-dasharray="75, 100" stroke-dashoffset="-10" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        {{-- Low Priority (Green 15%) --}}
                        <path class="text-emerald-500" stroke-dasharray="15, 100" stroke-dashoffset="-85" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span class="text-[9px] text-gray-400">Total</span>
                        <span class="text-xs font-bold font-mono text-gray-900 dark:text-white">{{ $kpis['pending_queue'] }}</span>
                    </div>
                </div>
                {{-- Legend --}}
                <div class="space-y-1.5 text-[11px] pt-2 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="w-2 h-2 rounded-full bg-rose-500"></span> High Priority</span>
                        <span class="font-mono text-gray-500 dark:text-gray-400">12,540 (10.0%)</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="w-2 h-2 rounded-full bg-blue-500"></span> Normal Priority</span>
                        <span class="font-mono text-gray-500 dark:text-gray-400">94,250 (75.1%)</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Low Priority</span>
                        <span class="font-mono text-gray-500 dark:text-gray-400">18,640 (14.9%)</span>
                    </div>
                </div>
            </div>

            {{-- Queue by Customer (Top 5) Table --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 flex flex-col justify-between shadow-xs">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">QUEUE BY CUSTOMER (TOP 5)</h3>
                    <a href="{{ url('/superman/users/customers') }}" class="text-[10px] text-primary-600 hover:text-primary-700 dark:text-primary-400 font-semibold">View All</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-[11px]">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-800 text-gray-400 text-[10px] uppercase">
                                <th class="pb-1.5 font-semibold">Customer</th>
                                <th class="pb-1.5 font-semibold text-amber-600 dark:text-amber-400">Pending</th>
                                <th class="pb-1.5 font-semibold text-blue-600 dark:text-blue-400">Processing</th>
                                <th class="pb-1.5 font-semibold text-emerald-600 dark:text-emerald-400">Sent</th>
                                <th class="pb-1.5 font-semibold text-rose-600 dark:text-rose-400 text-right">Failed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($topCustomers as $tc)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition-colors">
                                    <td class="py-1.5 font-medium text-gray-900 dark:text-white truncate max-w-[80px]">{{ $tc['name'] }}</td>
                                    <td class="py-1.5 font-mono text-amber-700 dark:text-amber-300 font-medium">{{ number_format($tc['pending']) }}</td>
                                    <td class="py-1.5 font-mono text-blue-700 dark:text-blue-300 font-medium">{{ number_format($tc['processing']) }}</td>
                                    <td class="py-1.5 font-mono text-emerald-700 dark:text-emerald-300 font-medium">{{ number_format($tc['sent']) }}</td>
                                    <td class="py-1.5 font-mono text-rose-700 dark:text-rose-300 text-right font-medium">{{ number_format($tc['failed']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-4 text-center text-gray-400">No customer records</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Queue by Status (Donut) --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 flex flex-col justify-between shadow-xs">
                <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-2">QUEUE BY STATUS</h3>
                <div class="flex items-center justify-center relative my-2">
                    <svg class="w-28 h-28 transform -rotate-90" viewBox="0 0 36 36">
                        <path class="text-gray-100 dark:text-gray-800" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        {{-- Pending (Amber 42.5%) --}}
                        <path class="text-amber-500" stroke-dasharray="42.5, 100" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        {{-- Processing (Blue 3%) --}}
                        <path class="text-blue-500" stroke-dasharray="3, 100" stroke-dashoffset="-42.5" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        {{-- Sent (Green 40.5%) --}}
                        <path class="text-emerald-500" stroke-dasharray="40.5, 100" stroke-dashoffset="-45.5" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        {{-- Failed (Red 1%) --}}
                        <path class="text-rose-500" stroke-dasharray="1, 100" stroke-dashoffset="-86" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        {{-- Scheduled (Purple 13%) --}}
                        <path class="text-purple-500" stroke-dasharray="13, 100" stroke-dashoffset="-87" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span class="text-[9px] text-gray-400">Total</span>
                        <span class="text-xs font-bold font-mono text-gray-900 dark:text-white">{{ $kpis['total_sms'] }}</span>
                    </div>
                </div>

                <div class="space-y-1 text-[10px] pt-2 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1 text-gray-600 dark:text-gray-300"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Pending</span>
                        <span class="font-mono text-gray-500 dark:text-gray-400">{{ $kpis['pending_queue'] }} (42.5%)</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1 text-gray-600 dark:text-gray-300"><span class="w-2 h-2 rounded-full bg-blue-500"></span> Processing</span>
                        <span class="font-mono text-gray-500 dark:text-gray-400">{{ $kpis['processing'] }} (2.9%)</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1 text-gray-600 dark:text-gray-300"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Sent</span>
                        <span class="font-mono text-gray-500 dark:text-gray-400">{{ $kpis['sent'] }} (40.6%)</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1 text-gray-600 dark:text-gray-300"><span class="w-2 h-2 rounded-full bg-rose-500"></span> Failed</span>
                        <span class="font-mono text-gray-500 dark:text-gray-400">{{ $kpis['failed'] }} (0.8%)</span>
                    </div>
                </div>
            </div>

            {{-- Worker Status (Donut) --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 flex flex-col justify-between shadow-xs">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">WORKER STATUS</h3>
                    <span class="text-[10px] text-primary-600 hover:text-primary-700 dark:text-primary-400 font-semibold cursor-pointer">View All</span>
                </div>
                <div class="flex items-center justify-center relative my-2">
                    <svg class="w-28 h-28 transform -rotate-90" viewBox="0 0 36 36">
                        <path class="text-gray-100 dark:text-gray-800" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        {{-- Active (Green 91.7%) --}}
                        <path class="text-emerald-500" stroke-dasharray="91.7, 100" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        {{-- Idle (Blue 4.2%) --}}
                        <path class="text-blue-500" stroke-dasharray="4.2, 100" stroke-dashoffset="-91.7" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        {{-- Busy (Orange 4.1%) --}}
                        <path class="text-amber-500" stroke-dasharray="4.1, 100" stroke-dashoffset="-95.9" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span class="text-sm font-bold font-mono text-gray-900 dark:text-white">24</span>
                        <span class="text-[9px] text-gray-400">Total</span>
                    </div>
                </div>

                <div class="space-y-1 text-[11px] pt-2 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Active</span>
                        <span class="font-mono text-gray-500 dark:text-gray-400">22 (91.7%)</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="w-2 h-2 rounded-full bg-blue-500"></span> Idle</span>
                        <span class="font-mono text-gray-500 dark:text-gray-400">1 (4.2%)</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Busy</span>
                        <span class="font-mono text-gray-500 dark:text-gray-400">1 (4.1%)</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="w-2 h-2 rounded-full bg-rose-500"></span> Offline</span>
                        <span class="font-mono text-gray-500 dark:text-gray-400">0 (0%)</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 5. Lower Section: Infrastructure & Service Health + Server Resources + System Alerts --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            {{-- Infrastructure & Service Health (6 cols) --}}
            <div class="lg:col-span-6 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 flex flex-col justify-between shadow-xs">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">INFRASTRUCTURE & SERVICE HEALTH</h3>
                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Operational
                    </span>
                </div>

                {{-- 8 Compact Service Tiles in 4x2 Grid --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    {{-- Redis --}}
                    <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-bold text-rose-600 dark:text-rose-400">REDIS</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        </div>
                        <div class="text-xs font-bold text-gray-900 dark:text-white">Online</div>
                        <div class="text-[9px] text-gray-500 dark:text-gray-400 mt-1">Uptime: 2d 14h</div>
                        <div class="text-[9px] text-gray-500 dark:text-gray-400">Memory: 312 MB</div>
                    </div>

                    {{-- Redis Database --}}
                    <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400">REDIS DB</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        </div>
                        <div class="text-xs font-bold text-gray-900 dark:text-white">Connected</div>
                        <div class="text-[9px] text-gray-500 dark:text-gray-400 mt-1">Keys: 812,543</div>
                        <div class="text-[9px] text-gray-500 dark:text-gray-400">Hit Rate: 98.6%</div>
                    </div>

                    {{-- Supervisor --}}
                    <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-bold text-sky-600 dark:text-sky-400">SUPERVISOR</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        </div>
                        <div class="text-xs font-bold text-gray-900 dark:text-white">Running</div>
                        <div class="text-[9px] text-gray-500 dark:text-gray-400 mt-1">Processes: 24</div>
                        <div class="text-[9px] text-gray-500 dark:text-gray-400">All programs ok</div>
                    </div>

                    {{-- PHP-FPM --}}
                    <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-bold text-purple-600 dark:text-purple-400">PHP-FPM</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        </div>
                        <div class="text-xs font-bold text-gray-900 dark:text-white">Running</div>
                        <div class="text-[9px] text-gray-500 dark:text-gray-400 mt-1">Version: {{ $phpFpm['php_version'] ?? PHP_VERSION }}</div>
                        <div class="text-[9px] text-gray-500 dark:text-gray-400">Active: 35</div>
                    </div>

                    {{-- NGINX --}}
                    <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">NGINX</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        </div>
                        <div class="text-xs font-bold text-gray-900 dark:text-white">Running</div>
                        <div class="text-[9px] text-gray-500 dark:text-gray-400 mt-1">Version: 1.24.0</div>
                        <div class="text-[9px] text-gray-500 dark:text-gray-400">Active Conn: 312</div>
                    </div>

                    {{-- MYSQL / MARIADB --}}
                    <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400">MYSQL</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        </div>
                        <div class="text-xs font-bold text-gray-900 dark:text-white">Connected</div>
                        <div class="text-[9px] text-gray-500 dark:text-gray-400 mt-1">Threads: {{ $db['active_threads'] ?? 1 }}</div>
                        <div class="text-[9px] text-emerald-600 dark:text-emerald-400 font-mono">{{ $db['latency_ms'] ?? '0.36' }} ms</div>
                    </div>

                    {{-- Queue Worker --}}
                    <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-bold text-cyan-600 dark:text-cyan-400">QUEUE WORKER</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        </div>
                        <div class="text-xs font-bold text-gray-900 dark:text-white">Active</div>
                        <div class="text-[9px] text-gray-500 dark:text-gray-400 mt-1">Workers: 24</div>
                        <div class="text-[9px] text-gray-500 dark:text-gray-400">Jobs: {{ $kpis['processing'] }}</div>
                    </div>

                    {{-- Cron Service --}}
                    <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400">CRON SERVICE</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        </div>
                        <div class="text-xs font-bold text-gray-900 dark:text-white">Running</div>
                        <div class="text-[9px] text-gray-500 dark:text-gray-400 mt-1">Last: 1 min ago</div>
                        <div class="text-[9px] text-gray-500 dark:text-gray-400">Next: in 1 min</div>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-gray-100 dark:border-gray-800 text-[10px] text-gray-500 dark:text-gray-400">
                    <span>Last Health Check: {{ now()->format('d M Y h:i:s A') }}</span>
                    <button type="button" wire:click="$refresh" class="px-2 py-1 rounded bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-primary-600 dark:text-primary-400 font-semibold cursor-pointer">
                        &circlearrowright; Check Now
                    </button>
                </div>
            </div>

            {{-- Server Resources (3 Radial Gauge Meters) (3 cols) --}}
            <div class="lg:col-span-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 flex flex-col justify-between shadow-xs">
                <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-2">SERVER RESOURCES</h3>

                <div class="grid grid-cols-3 gap-2 text-center my-2">
                    {{-- CPU Usage Gauge (23%) --}}
                    <div>
                        <div class="relative w-16 h-16 mx-auto flex items-center justify-center">
                            <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 36 36">
                                <path class="text-gray-100 dark:text-gray-800" stroke-width="3.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="text-emerald-500" stroke-dasharray="23, 100" stroke-width="3.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="absolute text-xs font-bold font-mono text-gray-900 dark:text-white">23%</span>
                        </div>
                        <span class="text-[10px] text-gray-500 dark:text-gray-400 block mt-1">CPU Usage</span>
                        <span class="text-[9px] text-gray-400 font-mono">Cores: {{ $load['cpu_cores'] ?? 8 }}</span>
                    </div>

                    {{-- RAM Usage Gauge (61%) --}}
                    <div>
                        <div class="relative w-16 h-16 mx-auto flex items-center justify-center">
                            <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 36 36">
                                <path class="text-gray-100 dark:text-gray-800" stroke-width="3.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="text-amber-500" stroke-dasharray="61, 100" stroke-width="3.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="absolute text-xs font-bold font-mono text-gray-900 dark:text-white">61%</span>
                        </div>
                        <span class="text-[10px] text-gray-500 dark:text-gray-400 block mt-1">RAM Usage</span>
                        <span class="text-[9px] text-gray-400 font-mono">3.8G / 6.2G</span>
                    </div>

                    {{-- Disk Usage Gauge (47%) --}}
                    <div>
                        <div class="relative w-16 h-16 mx-auto flex items-center justify-center">
                            <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 36 36">
                                <path class="text-gray-100 dark:text-gray-800" stroke-width="3.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="text-cyan-500" stroke-dasharray="{{ min(100, $disk['used_percentage'] ?? 47) }}, 100" stroke-width="3.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="absolute text-xs font-bold font-mono text-gray-900 dark:text-white">{{ $disk['used_percentage'] ?? 47 }}%</span>
                        </div>
                        <span class="text-[10px] text-gray-500 dark:text-gray-400 block mt-1">Disk Usage</span>
                        <span class="text-[9px] text-gray-400 font-mono">{{ $disk['used_gb'] ?? 47 }}G / {{ $disk['total_gb'] ?? 100 }}G</span>
                    </div>
                </div>

                <div class="space-y-1 text-[10px] text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex justify-between">
                        <span>Load Average:</span>
                        <span class="font-mono text-gray-800 dark:text-gray-200">0.65, 0.72, 0.58</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Network In:</span>
                        <span class="font-mono text-gray-800 dark:text-gray-200">125.6 Mbps</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Network Out:</span>
                        <span class="font-mono text-gray-800 dark:text-gray-200">48.3 Mbps</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Server Uptime:</span>
                        <span class="font-mono text-gray-800 dark:text-gray-200">2d 14h 32m 45s</span>
                    </div>
                </div>
            </div>

            {{-- System Alerts (3 cols) --}}
            <div class="lg:col-span-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 flex flex-col justify-between shadow-xs">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">SYSTEM ALERTS</h3>
                    <span class="text-[10px] text-primary-600 hover:text-primary-700 dark:text-primary-400 font-semibold cursor-pointer">View All</span>
                </div>

                <div class="space-y-2.5 my-1">
                    @foreach($alerts as $al)
                        <div class="flex items-start justify-between gap-2 text-xs">
                            <div class="flex items-start gap-1.5 min-w-0">
                                @if($al['type'] === 'critical')
                                    <x-heroicon-s-exclamation-circle class="w-4 h-4 text-rose-500 flex-shrink-0 mt-0.5" />
                                @elseif($al['type'] === 'warning')
                                    <x-heroicon-s-exclamation-triangle class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" />
                                @else
                                    <x-heroicon-s-information-circle class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" />
                                @endif
                                <span class="text-gray-700 dark:text-gray-300 text-[11px] leading-tight truncate">{{ $al['title'] }}</span>
                            </div>
                            <span class="text-[10px] text-gray-400 font-mono flex-shrink-0">{{ $al['time'] }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="pt-2 border-t border-gray-100 dark:border-gray-800 text-[10px] text-gray-400 text-center">
                    Alert stream synchronized with system kernel
                </div>
            </div>
        </div>

        {{-- 6. Bottom Action Command Bar (Clean Application Theme) --}}
        <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
            <div class="flex flex-wrap items-center gap-2.5">
                {{-- Pause All Queues --}}
                <button
                    type="button"
                    wire:click="togglePause"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 text-emerald-700 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300 text-xs font-bold transition-colors cursor-pointer"
                >
                    <x-heroicon-m-pause class="w-3.5 h-3.5" />
                    Pause All Queues
                </button>

                {{-- Resume All Queues --}}
                <button
                    type="button"
                    wire:click="togglePause"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition-colors cursor-pointer shadow-sm shadow-blue-500/20"
                >
                    <x-heroicon-m-play class="w-3.5 h-3.5" />
                    Resume All Queues
                </button>

                {{-- Retry Failed --}}
                <button
                    type="button"
                    wire:click="retryFailed"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition-colors cursor-pointer shadow-sm"
                >
                    <x-heroicon-m-arrow-path class="w-3.5 h-3.5" />
                    Retry Failed
                </button>

                {{-- Requeue --}}
                <button
                    type="button"
                    wire:click="retryFailed"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold transition-colors cursor-pointer shadow-sm"
                >
                    <x-heroicon-m-arrow-path-rounded-square class="w-3.5 h-3.5" />
                    Requeue
                </button>

                {{-- Cancel Selected --}}
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-colors cursor-pointer shadow-sm"
                >
                    <x-heroicon-m-x-mark class="w-3.5 h-3.5" />
                    Cancel Selected
                </button>
            </div>

            <div>
                {{-- Export Logs --}}
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-white hover:bg-gray-50 border border-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-xs font-bold transition-colors cursor-pointer shadow-xs"
                >
                    <x-heroicon-m-arrow-down-tray class="w-3.5 h-3.5" />
                    Export Logs
                </button>
            </div>
        </div>
    </div>
</div>
