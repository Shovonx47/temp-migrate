<div wire:poll.3s="getGatewayActivity">
    <div class="fi-wi-stats-overview-stat rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-500/10">
                    <x-heroicon-o-signal class="w-5 h-5 text-emerald-500" />
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">Live Gateway Monitor</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Real-time gateway throughput & health</p>
                </div>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                </span>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">STREAMING</span>
            </div>
        </div>

        {{-- Gateway Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
            @forelse ($this->getGatewayActivity() as $gw)
                <div class="relative rounded-lg border p-3 transition-all duration-300
                    {{ $gw['is_active']
                        ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-950/20'
                        : ($gw['is_enabled']
                            ? 'border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30'
                            : 'border-red-200 dark:border-red-900 bg-red-50/30 dark:bg-red-950/10 opacity-60') }}">

                    {{-- Gateway header --}}
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="relative flex h-2 w-2 shrink-0">
                                @if ($gw['is_active'])
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                @elseif ($gw['is_enabled'])
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-gray-400"></span>
                                @else
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                @endif
                            </span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $gw['name'] }}</span>
                        </div>
                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 uppercase">{{ $gw['driver'] }}</span>
                    </div>

                    {{-- Throughput --}}
                    <div class="flex items-baseline gap-1 mb-2">
                        <span class="text-2xl font-bold {{ $gw['is_active'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $gw['throughput'] }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">msg/min</span>
                        @if ($gw['latency'])
                            <span class="ml-auto text-[10px] font-mono {{ $gw['latency'] < 500 ? 'text-emerald-600 dark:text-emerald-400' : ($gw['latency'] < 2000 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">{{ $gw['latency'] }}ms</span>
                        @endif
                    </div>

                    {{-- Stats bar --}}
                    @if ($gw['total'] > 0)
                        <div class="w-full h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden mb-2">
                            @php
                                $sentPct = $gw['total'] > 0 ? ($gw['sent'] / $gw['total']) * 100 : 0;
                                $failPct = $gw['total'] > 0 ? ($gw['failed'] / $gw['total']) * 100 : 0;
                                $pendPct = $gw['total'] > 0 ? ($gw['pending'] / $gw['total']) * 100 : 0;
                            @endphp
                            <div class="h-full flex">
                                <div class="bg-emerald-500 transition-all duration-500" style="width: {{ $sentPct }}%"></div>
                                <div class="bg-red-500 transition-all duration-500" style="width: {{ $failPct }}%"></div>
                                <div class="bg-amber-400 transition-all duration-500" style="width: {{ $pendPct }}%"></div>
                            </div>
                        </div>
                    @else
                        <div class="w-full h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 mb-2"></div>
                    @endif

                    {{-- Counts row --}}
                    <div class="flex items-center justify-between text-[11px]">
                        <div class="flex items-center gap-2">
                            <span class="text-emerald-600 dark:text-emerald-400 font-medium">✓ {{ $gw['sent'] }}</span>
                            <span class="text-red-500 dark:text-red-400 font-medium">✗ {{ $gw['failed'] }}</span>
                            <span class="text-amber-500 dark:text-amber-400 font-medium">⏳ {{ $gw['pending'] }}</span>
                        </div>
                        <span class="text-gray-500 dark:text-gray-400 font-mono">৳{{ number_format($gw['cost'], 2) }}</span>
                    </div>

                    {{-- Success rate badge --}}
                    @if ($gw['success_rate'] !== null)
                        <div class="absolute top-2 right-2">
                            <span class="text-[9px] font-bold px-1 py-0.5 rounded
                                {{ $gw['success_rate'] >= 90 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'
                                    : ($gw['success_rate'] >= 70 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400'
                                    : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400') }}">
                                {{ $gw['success_rate'] }}%
                            </span>
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-span-full py-8 text-center text-gray-400 dark:text-gray-500">
                    <x-heroicon-o-signal class="w-8 h-8 mx-auto mb-2 opacity-50" />
                    <p class="text-sm">No gateways configured</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
