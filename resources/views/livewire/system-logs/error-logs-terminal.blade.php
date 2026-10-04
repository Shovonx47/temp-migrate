<div
    wire:poll.6000ms="refresh"
    class="space-y-4"
>
    {{-- Top Controls --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        {{-- Traffic-light dots + title --}}
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-1.5">
                <span class="h-3 w-3 rounded-full bg-red-500"></span>
                <span class="h-3 w-3 rounded-full bg-yellow-400"></span>
                <span class="h-3 w-3 rounded-full bg-green-500"></span>
            </div>
            <span class="font-mono text-sm text-gray-400 dark:text-gray-500">
                error-monitor &mdash; live error stream
            </span>
        </div>

        {{-- Right: filter pills + actions --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- LIVE toggle --}}
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

    {{-- Provider / Customer Filter Pills --}}
    <div class="flex items-center gap-3">
        <span class="font-mono text-xs text-gray-500">Filter by:</span>
        <div class="flex items-center gap-1 rounded-lg bg-[#0d1117] p-1 ring-1 ring-white/5">
            <button
                type="button"
                wire:click="setFilter('provider')"
                class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-xs font-medium transition-all duration-150
                    {{ $filter === 'provider'
                        ? 'bg-orange-500/20 text-orange-300 shadow-sm ring-1 ring-orange-500/30'
                        : 'text-gray-500 hover:text-gray-300 hover:bg-white/5'
                    }}"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z" />
                </svg>
                Provider
                <span class="font-mono text-xs opacity-60">(Infozillion / Gateway)</span>
            </button>

            <button
                type="button"
                wire:click="setFilter('customer')"
                class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-xs font-medium transition-all duration-150
                    {{ $filter === 'customer'
                        ? 'bg-blue-500/20 text-blue-300 shadow-sm ring-1 ring-blue-500/30'
                        : 'text-gray-500 hover:text-gray-300 hover:bg-white/5'
                    }}"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
                Customer
                <span class="font-mono text-xs opacity-60">(User / Operator Side)</span>
            </button>
        </div>
    </div>

    {{-- Description for active filter --}}
    @if ($filter === 'provider')
        <div class="flex items-start gap-2 rounded-lg border border-orange-500/20 bg-orange-500/5 px-3 py-2">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <p class="font-mono text-xs text-orange-300/80">
                Showing <span class="font-bold text-orange-300">Provider / Gateway</span> errors — Infozillion API failures, SMSC connection issues, routing errors, and outbound HTTP timeouts.
            </p>
        </div>
    @else
        <div class="flex items-start gap-2 rounded-lg border border-blue-500/20 bg-blue-500/5 px-3 py-2">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <p class="font-mono text-xs text-blue-300/80">
                Showing <span class="font-bold text-blue-300">Customer / Operator</span> errors — authentication failures, insufficient balance, sender issues, routing misconfigurations, and validation errors on the user side.
            </p>
        </div>
    @endif

    {{-- Terminal Window --}}
    <div
        class="relative overflow-hidden rounded-xl bg-[#0d1117] shadow-2xl ring-1 ring-white/5"
        style="min-height: 500px;"
    >
        <div class="pointer-events-none absolute inset-0" style="background-image: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0,0,0,0.03) 2px, rgba(0,0,0,0.03) 4px);"></div>

        {{-- Title bar --}}
        <div class="flex items-center gap-2 border-b border-white/5 bg-[#161b22] px-4 py-2">
            <svg class="h-4 w-4 {{ $filter === 'provider' ? 'text-orange-400' : 'text-blue-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <span class="font-mono text-xs {{ $filter === 'provider' ? 'text-orange-400/80' : 'text-blue-400/80' }}">
                Error Monitor — {{ $filter === 'provider' ? 'Provider / Infozillion Gateway' : 'Customer / Operator Side' }}
            </span>
            <span class="ml-auto font-mono text-xs text-gray-600">{{ count($lines) }} errors</span>
        </div>

        {{-- Log output --}}
        <div
            id="error-log-terminal"
            class="h-[500px] overflow-y-auto p-4 font-mono text-xs leading-relaxed"
            x-data
            x-init="$el.scrollTop = $el.scrollHeight"
            x-on:livewire:update.window="$nextTick(() => { $el.scrollTop = $el.scrollHeight })"
        >
            @forelse ($lines as $line)
                @php
                    $lower = strtolower($line);
                    $colorClass = 'text-red-400';
                    $prefixColor = 'text-red-600';

                    if (str_contains($lower, '.warning') || str_contains($lower, 'warning')) {
                        $colorClass = 'text-yellow-400';
                        $prefixColor = 'text-yellow-600';
                    } elseif (str_contains($lower, 'timeout') || str_contains($lower, 'connection')) {
                        $colorClass = 'text-orange-400';
                        $prefixColor = 'text-orange-600';
                    } elseif (str_contains($lower, 'exception')) {
                        $colorClass = 'text-red-300';
                        $prefixColor = 'text-red-500';
                    }
                @endphp
                <div class="group flex gap-2 hover:bg-white/[0.02] py-0.5 px-1 rounded">
                    <span class="{{ $prefixColor }} select-none">⚠</span>
                    <span class="{{ $colorClass }} break-all">{{ $line }}</span>
                </div>
            @empty
                <div class="flex h-full flex-col items-center justify-center gap-3 text-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-green-500/10 ring-1 ring-green-500/20">
                        <svg class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-green-400 font-semibold">All Clear</p>
                        <p class="text-gray-600 text-xs mt-1">
                            No {{ $filter === 'provider' ? 'provider/gateway' : 'customer-side' }} errors detected.
                        </p>
                    </div>
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
        <span class="font-mono text-xs text-gray-600">Error Types:</span>
        <span class="flex items-center gap-1.5 font-mono text-xs text-red-400">
            <span class="h-2 w-2 rounded-full bg-red-500"></span> Exception / Error
        </span>
        <span class="flex items-center gap-1.5 font-mono text-xs text-orange-400">
            <span class="h-2 w-2 rounded-full bg-orange-500"></span> Timeout / Connection
        </span>
        <span class="flex items-center gap-1.5 font-mono text-xs text-yellow-400">
            <span class="h-2 w-2 rounded-full bg-yellow-500"></span> Warning
        </span>
        <span class="ml-auto font-mono text-xs text-gray-700">Auto-refreshes every 6s</span>
    </div>
</div>
