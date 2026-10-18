<div wire:poll.3s="getActiveBatches">
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden h-full">
        {{-- Header --}}
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="flex items-center justify-center w-7 h-7 rounded-lg bg-cyan-500/10">
                        <x-heroicon-o-server-stack class="w-4 h-4 text-cyan-500" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Batch Monitor</h3>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400">Active & recent batches</p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-cyan-500"></span>
                    </span>
                    <span class="text-[10px] font-medium text-cyan-600 dark:text-cyan-400">LIVE</span>
                </div>
            </div>
        </div>

        {{-- Batch List --}}
        <div class="divide-y divide-gray-50 dark:divide-gray-800">
            @forelse ($this->getActiveBatches() as $batch)
                @php
                    $statusColors = match($batch['status']) {
                        'pending' => ['badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', 'bar' => 'bg-amber-500'],
                        'processing' => ['badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', 'bar' => 'bg-blue-500'],
                        'completed' => ['badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', 'bar' => 'bg-emerald-500'],
                        'failed' => ['badge' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', 'bar' => 'bg-red-500'],
                        default => ['badge' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400', 'bar' => 'bg-gray-400'],
                    };
                    $isActive = in_array($batch['status'], ['pending', 'processing']);
                @endphp
                <div class="px-4 py-2.5 hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors {{ $isActive ? 'border-l-2 border-l-blue-500' : '' }}">
                    {{-- Top row --}}
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-[10px] font-mono text-gray-400">#{{ $batch['id'] }}</span>
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold {{ $statusColors['badge'] }}">
                                @if ($isActive)
                                    <span class="relative flex h-1.5 w-1.5">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $statusColors['bar'] }} opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 {{ $statusColors['bar'] }}"></span>
                                    </span>
                                @endif
                                {{ strtoupper($batch['status']) }}
                            </span>
                            <span class="text-xs text-gray-700 dark:text-gray-300 truncate">{{ $batch['customer_name'] }}</span>
                        </div>
                        <span class="text-[10px] text-gray-400 flex-shrink-0">{{ $batch['time_ago'] }}</span>
                    </div>

                    {{-- Progress bar --}}
                    <div class="w-full h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden mb-1.5">
                        <div class="{{ $statusColors['bar'] }} h-full rounded-full transition-all duration-700 {{ $isActive ? 'animate-pulse' : '' }}"
                             style="width: {{ min($batch['progress'], 100) }}%"></div>
                    </div>

                    {{-- Details row --}}
                    <div class="flex items-center justify-between text-[10px]">
                        <div class="flex items-center gap-2">
                            <span class="text-gray-500">{{ $batch['progress_label'] }}</span>
                            <span class="text-gray-400">{{ $batch['valid_count'] }}/{{ $batch['total_recipients'] }}</span>
                            @if ($batch['invalid_count'] > 0)
                                <span class="text-red-500">{{ $batch['invalid_count'] }} invalid</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-gray-400">{{ $batch['sender_id'] }}</span>
                            <span class="px-1 py-0 rounded text-[8px] font-bold
                                {{ $batch['source_type'] === 'api' ? 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'
                                    : ($batch['source_type'] === 'upload' ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400'
                                    : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400') }}">
                                {{ strtoupper($batch['source_type']) }}
                            </span>
                        </div>
                    </div>

                    {{-- Message preview --}}
                    @if ($batch['body'])
                        <p class="mt-1 text-[10px] text-gray-400 dark:text-gray-500 truncate italic">"{{ $batch['body'] }}"</p>
                    @endif
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <x-heroicon-o-server-stack class="w-8 h-8 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                    <p class="text-xs text-gray-400 dark:text-gray-500">No active batches</p>
                    <p class="text-[10px] text-gray-300 dark:text-gray-600">Waiting for batch jobs...</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
