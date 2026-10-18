<div
    wire:poll.5000ms="refresh"
    class="space-y-4"
>
    {{-- Terminal Header Bar --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            {{-- Traffic-light dots --}}
            <div class="flex items-center gap-1.5">
                <span class="h-3 w-3 rounded-full bg-red-500"></span>
                <span class="h-3 w-3 rounded-full bg-yellow-400"></span>
                <span class="h-3 w-3 rounded-full bg-green-500"></span>
            </div>
            <span class="font-mono text-sm text-gray-400 dark:text-gray-500">
                api-monitor &mdash; live 3rd-party API stream
            </span>
        </div>

        <div class="flex items-center gap-2">
            {{-- Auto-refresh toggle --}}
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

            {{-- Refresh button --}}
            <button
                type="button"
                wire:click="refresh"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-1.5 rounded-md bg-gray-500/10 px-2.5 py-1.5 text-xs font-medium text-gray-400 ring-1 ring-gray-500/20 transition-colors hover:bg-gray-500/20 disabled:opacity-50"
            >
                <svg wire:loading.remove wire:target="refresh" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <svg wire:loading wire:target="refresh" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                REFRESH
            </button>

            {{-- Clear button --}}
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

    {{-- Terminal Window --}}
    <div
        class="relative overflow-hidden rounded-xl bg-[#0d1117] shadow-2xl ring-1 ring-white/5"
        style="min-height: 520px;"
    >
        {{-- Scanline effect overlay --}}
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-black/20" style="background-image: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0,0,0,0.03) 2px, rgba(0,0,0,0.03) 4px);"></div>

        {{-- Top title bar --}}
        <div class="flex items-center gap-2 border-b border-white/5 bg-[#161b22] px-4 py-2">
            <svg class="h-4 w-4 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
            </svg>
            <span class="font-mono text-xs text-cyan-400/80">API Activity Monitor</span>
            <span class="ml-auto font-mono text-xs text-gray-600">{{ count($lines) }} entries</span>
        </div>

        {{-- Log lines --}}
        <div
            id="api-log-terminal"
            class="h-[520px] overflow-y-auto p-4 font-mono text-xs leading-relaxed"
            x-data
            x-init="$el.scrollTop = $el.scrollHeight"
            x-on:livewire:update.window="$nextTick(() => { $el.scrollTop = $el.scrollHeight })"
        >
            @forelse ($lines as $line)
                @php
                    $lower = strtolower($line);
                    $colorClass = 'text-gray-400';
                    $prefixColor = 'text-cyan-500';

                    if (str_contains($lower, '.error') || str_contains($lower, 'failed') || str_contains($lower, 'exception') || str_contains($lower, 'error:')) {
                        $colorClass = 'text-red-400';
                        $prefixColor = 'text-red-600';
                    } elseif (str_contains($lower, '.warning') || str_contains($lower, 'warning')) {
                        $colorClass = 'text-yellow-400';
                        $prefixColor = 'text-yellow-600';
                    } elseif (str_contains($lower, 'infozillion') || str_contains($lower, 'gateway') || str_contains($lower, 'http')) {
                        $colorClass = 'text-cyan-300';
                        $prefixColor = 'text-cyan-600';
                    } elseif (str_contains($lower, 'success') || str_contains($lower, 'sent') || str_contains($lower, 'delivered')) {
                        $colorClass = 'text-green-400';
                        $prefixColor = 'text-green-600';
                    }
                @endphp
                <div class="group flex gap-2 hover:bg-white/[0.02] py-0.5 px-1 rounded">
                    <span class="{{ $prefixColor }} select-none">›</span>
                    <span class="{{ $colorClass }} break-all">{{ $line }}</span>
                </div>
            @empty
                <div class="flex h-full flex-col items-center justify-center gap-3 text-center">
                    <svg class="h-10 w-10 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
                    </svg>
                    <p class="text-gray-600">No API activity recorded yet.</p>
                    <p class="text-gray-700 text-xs">Significant API calls will appear here automatically.</p>
                </div>
            @endforelse

            {{-- Blinking cursor --}}
            <div class="mt-1 flex items-center gap-1">
                <span class="text-green-400">$</span>
                <span class="h-4 w-2 animate-pulse bg-green-500/70 rounded-sm"></span>
            </div>
        </div>
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap items-center gap-4 px-1">
        <span class="font-mono text-xs text-gray-600">Legend:</span>
        <span class="flex items-center gap-1.5 font-mono text-xs text-cyan-400">
            <span class="h-2 w-2 rounded-full bg-cyan-500"></span> API / Gateway
        </span>
        <span class="flex items-center gap-1.5 font-mono text-xs text-green-400">
            <span class="h-2 w-2 rounded-full bg-green-500"></span> Success
        </span>
        <span class="flex items-center gap-1.5 font-mono text-xs text-yellow-400">
            <span class="h-2 w-2 rounded-full bg-yellow-500"></span> Warning
        </span>
        <span class="flex items-center gap-1.5 font-mono text-xs text-red-400">
            <span class="h-2 w-2 rounded-full bg-red-500"></span> Error
        </span>
        <span class="ml-auto font-mono text-xs text-gray-700">Auto-refreshes every 5s</span>
    </div>
</div>
