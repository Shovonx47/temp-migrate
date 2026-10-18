<div
    wire:poll.4000ms="pollNewLogs"
    class="space-y-4"
>
    {{-- Controls bar --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        {{-- Left: title + dot --}}
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-1.5">
                <span class="h-3 w-3 rounded-full bg-red-500"></span>
                <span class="h-3 w-3 rounded-full bg-yellow-400"></span>
                <span class="h-3 w-3 rounded-full bg-green-500"></span>
            </div>
            <span class="font-mono text-sm text-gray-400 dark:text-gray-500">
                sms-monitor &mdash; live SMS processing stream
            </span>
        </div>

        {{-- Right: actions --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- Level filter --}}
            <select
                wire:model.live="filterLevel"
                wire:change="applyFilters"
                class="rounded-md border-0 bg-[#161b22] px-2.5 py-1.5 font-mono text-xs text-gray-400 ring-1 ring-white/10 focus:ring-cyan-500"
            >
                <option value="">All Levels</option>
                @foreach ($levelOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            {{-- Status filter --}}
            <select
                wire:model.live="filterStatus"
                wire:change="applyFilters"
                class="rounded-md border-0 bg-[#161b22] px-2.5 py-1.5 font-mono text-xs text-gray-400 ring-1 ring-white/10 focus:ring-cyan-500"
            >
                <option value="">All Statuses</option>
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            @if ($filterLevel !== '' || $filterStatus !== '')
                <button
                    type="button"
                    wire:click="clearFilters"
                    class="inline-flex items-center gap-1 rounded-md bg-orange-500/10 px-2.5 py-1.5 text-xs font-medium text-orange-400 ring-1 ring-orange-500/20 transition-colors hover:bg-orange-500/20"
                >
                    ✕ Clear Filters
                </button>
            @endif

            {{-- Live toggle --}}
            <button
                type="button"
                wire:click="$toggle('autoRefresh')"
                class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium transition-colors
                    {{ $autoRefresh
                        ? 'bg-green-500/10 text-green-400 ring-1 ring-green-500/30 hover:bg-green-500/20'
                        : 'bg-gray-500/10 text-gray-400 ring-1 ring-gray-500/20 hover:bg-gray-500/20'
                    }}"
            >
                <span class="relative flex h-2 w-2">
                    @if ($autoRefresh)
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                    @endif
                    <span class="relative inline-flex h-2 w-2 rounded-full {{ $autoRefresh ? 'bg-green-500' : 'bg-gray-500' }}"></span>
                </span>
                {{ $autoRefresh ? 'LIVE' : 'PAUSED' }}
            </button>

            {{-- Reload --}}
            <button
                type="button"
                wire:click="loadLogs"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-1.5 rounded-md bg-gray-500/10 px-2.5 py-1.5 text-xs font-medium text-gray-400 ring-1 ring-gray-500/20 transition-colors hover:bg-gray-500/20 disabled:opacity-50"
            >
                <svg wire:loading.remove wire:target="loadLogs" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <svg wire:loading wire:target="loadLogs" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                RELOAD
            </button>

            {{-- Clear --}}
            <button
                type="button"
                wire:click="clearTerminal"
                class="inline-flex items-center gap-1.5 rounded-md bg-red-500/10 px-2.5 py-1.5 text-xs font-medium text-red-400 ring-1 ring-red-500/20 transition-colors hover:bg-red-500/20"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                CLEAR
            </button>
        </div>
    </div>

    {{-- Stats bar --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @php
            $counts = collect($lines)->countBy(fn ($l) => $l['status'] ?? 'unknown');
        @endphp
        @foreach ([
            ['label' => 'Sent', 'key' => 'sent', 'color' => 'text-blue-400', 'dot' => 'bg-blue-500'],
            ['label' => 'Delivered', 'key' => 'delivered', 'color' => 'text-green-400', 'dot' => 'bg-green-500'],
            ['label' => 'Failed', 'key' => 'failed', 'color' => 'text-red-400', 'dot' => 'bg-red-500'],
            ['label' => 'Pending', 'key' => 'pending', 'color' => 'text-yellow-400', 'dot' => 'bg-yellow-500'],
        ] as $stat)
            <div class="flex items-center gap-3 rounded-lg bg-[#0d1117] px-3 py-2 ring-1 ring-white/5">
                <span class="h-2.5 w-2.5 flex-shrink-0 rounded-full {{ $stat['dot'] }}"></span>
                <div>
                    <p class="font-mono text-xs text-gray-500">{{ $stat['label'] }}</p>
                    <p class="font-mono text-base font-semibold {{ $stat['color'] }}">{{ $counts[$stat['key']] ?? 0 }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Terminal Window --}}
    <div
        class="relative overflow-hidden rounded-xl bg-[#0d1117] shadow-2xl ring-1 ring-white/5"
        style="min-height: 480px;"
    >
        <div class="pointer-events-none absolute inset-0" style="background-image: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0,0,0,0.03) 2px, rgba(0,0,0,0.03) 4px);"></div>

        {{-- Title bar --}}
        <div class="flex items-center gap-2 border-b border-white/5 bg-[#161b22] px-4 py-2">
            <svg class="h-4 w-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
            </svg>
            <span class="font-mono text-xs text-purple-400/80">SMS Processing Log — Database</span>
            <span class="ml-auto font-mono text-xs text-gray-600">{{ count($lines) }} / {{ $perPage }} entries</span>
        </div>

        {{-- Log output --}}
        <div
            id="sms-log-terminal"
            class="h-[480px] overflow-y-auto p-4 font-mono text-xs leading-relaxed"
            x-data
            x-init="$el.scrollTop = $el.scrollHeight"
            x-on:livewire:update.window="$nextTick(() => { $el.scrollTop = $el.scrollHeight })"
        >
            @forelse ($lines as $entry)
                @php
                    $levelColor = match ($entry['level']) {
                        'ERROR'   => 'text-red-400',
                        'WARNING' => 'text-yellow-400',
                        'DEBUG'   => 'text-gray-500',
                        default   => 'text-cyan-300',
                    };
                    $statusColor = match ($entry['status'] ?? '') {
                        'delivered' => 'text-green-400',
                        'sent'      => 'text-blue-400',
                        'failed'    => 'text-red-400',
                        'sending'   => 'text-yellow-400',
                        default     => 'text-gray-500',
                    };
                @endphp
                <div class="group flex flex-wrap gap-x-2 gap-y-0.5 hover:bg-white/[0.02] py-0.5 px-1 rounded">
                    <span class="text-gray-600 select-none">[{{ $entry['ts'] }}]</span>
                    <span class="font-bold {{ $levelColor }}">[{{ $entry['level'] }}]</span>
                    <span class="text-purple-300 font-semibold">{{ $entry['event'] }}</span>
                    @if ($entry['phone'])
                        <span class="text-gray-400">TO=<span class="text-white">{{ $entry['phone'] }}</span></span>
                    @endif
                    @if ($entry['gateway'])
                        <span class="text-gray-400">GW=<span class="text-cyan-400">{{ $entry['gateway'] }}</span></span>
                    @endif
                    @if ($entry['operator'])
                        <span class="text-gray-400">OP=<span class="text-blue-400">{{ $entry['operator'] }}</span></span>
                    @endif
                    @if ($entry['status'])
                        <span class="{{ $statusColor }} font-semibold">{{ strtoupper($entry['status']) }}</span>
                    @endif
                    @if ($entry['parts'] > 1)
                        <span class="text-gray-500">PARTS={{ $entry['parts'] }}</span>
                    @endif
                    @if ($entry['cost'] > 0)
                        <span class="text-gray-500">COST={{ number_format($entry['cost'], 4) }}</span>
                    @endif
                    @if ($entry['duration_ms'])
                        <span class="text-gray-500">{{ $entry['duration_ms'] }}ms</span>
                    @endif
                    @if ($entry['error'])
                        <span class="text-red-400 break-all">ERR="{{ $entry['error'] }}"</span>
                    @endif
                </div>
            @empty
                <div class="flex h-full flex-col items-center justify-center gap-3 text-center">
                    <svg class="h-10 w-10 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                    </svg>
                    <p class="text-gray-600">No SMS log entries found.</p>
                    <p class="text-gray-700 text-xs">SMS activity will be logged here when messages are processed.</p>
                </div>
            @endforelse

            <div class="mt-1 flex items-center gap-1">
                <span class="text-green-400">$</span>
                <span class="h-4 w-2 animate-pulse rounded-sm bg-green-500/70"></span>
            </div>
        </div>
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap items-center gap-4 px-1">
        <span class="font-mono text-xs text-gray-600">Legend:</span>
        <span class="flex items-center gap-1.5 font-mono text-xs text-blue-400"><span class="h-2 w-2 rounded-full bg-blue-500"></span> Sent</span>
        <span class="flex items-center gap-1.5 font-mono text-xs text-green-400"><span class="h-2 w-2 rounded-full bg-green-500"></span> Delivered</span>
        <span class="flex items-center gap-1.5 font-mono text-xs text-red-400"><span class="h-2 w-2 rounded-full bg-red-500"></span> Failed</span>
        <span class="flex items-center gap-1.5 font-mono text-xs text-yellow-400"><span class="h-2 w-2 rounded-full bg-yellow-500"></span> Sending</span>
        <span class="flex items-center gap-1.5 font-mono text-xs text-gray-500"><span class="h-2 w-2 rounded-full bg-gray-500"></span> Pending</span>
        <span class="ml-auto font-mono text-xs text-gray-700">Stored in DB · Auto-polls every 4s</span>
    </div>
</div>
