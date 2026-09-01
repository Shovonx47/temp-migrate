@php
    $messages = $this->getMessages();
    $pollInterval = $isPaused ? null : '2s';
@endphp

<div @if($pollInterval) wire:poll.{{ $pollInterval }}="getMessages" @endif class="w-full">
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
        {{-- Header Bar --}}
        <div class="p-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                {{-- Title & Live Beacon --}}
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400">
                        <x-heroicon-o-bolt class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-bold text-gray-900 dark:text-white tracking-tight">Live Action Stream</h2>
                            @if(!$isPaused)
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    LIVE STREAMING
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                    <x-heroicon-o-pause class="w-2.5 h-2.5" />
                                    STREAM PAUSED
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Real-time monitor of every customer action, message dispatched, gateway route, and carrier status
                        </p>
                    </div>
                </div>

                {{-- Quick Actions & Controls --}}
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Search --}}
                    <div class="relative min-w-[200px]">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Filter by customer, phone, text..."
                            class="w-full text-xs rounded-lg border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 dark:text-white py-1.5 pl-8 pr-3 focus:border-emerald-500 focus:ring-emerald-500"
                        />
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-400 absolute left-2.5 top-2" />
                    </div>

                    {{-- View Mode Switch --}}
                    <div class="flex items-center rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 p-0.5">
                        <button
                            type="button"
                            wire:click="setViewMode('stream')"
                            class="px-2.5 py-1 text-xs font-medium rounded-md transition-all {{ $viewMode === 'stream' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                        >
                            <span class="flex items-center gap-1">
                                <x-heroicon-o-list-bullet class="w-3.5 h-3.5" />
                                Stream
                            </span>
                        </button>
                        <button
                            type="button"
                            wire:click="setViewMode('table')"
                            class="px-2.5 py-1 text-xs font-medium rounded-md transition-all {{ $viewMode === 'table' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                        >
                            <span class="flex items-center gap-1">
                                <x-heroicon-o-table-cells class="w-3.5 h-3.5" />
                                Table
                            </span>
                        </button>
                    </div>

                    {{-- Pause / Play Stream Button --}}
                    <button
                        type="button"
                        wire:click="togglePause"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-medium transition-colors
                            {{ $isPaused
                                ? 'bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300'
                                : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-750' }}"
                    >
                        @if($isPaused)
                            <x-heroicon-o-play class="w-3.5 h-3.5" />
                            Resume Stream
                        @else
                            <x-heroicon-o-pause class="w-3.5 h-3.5" />
                            Pause Stream
                        @endif
                    </button>
                </div>
            </div>

            {{-- Filter Pills Bar --}}
            <div class="flex flex-wrap items-center justify-between gap-2 mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 text-xs">
                {{-- Status Pills --}}
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mr-1">Status:</span>
                    <button wire:click="setStatusFilter(null)"
                        class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ !$statusFilter ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400' }}">
                        All
                    </button>
                    <button wire:click="setStatusFilter('active')"
                        class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $statusFilter === 'active' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-300' }}">
                        ⚡ Active / In-Transit
                    </button>
                    <button wire:click="setStatusFilter('delivered')"
                        class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $statusFilter === 'delivered' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300' }}">
                        ✓ Delivered
                    </button>
                    <button wire:click="setStatusFilter('sent')"
                        class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $statusFilter === 'sent' ? 'bg-cyan-600 text-white' : 'bg-cyan-50 text-cyan-700 hover:bg-cyan-100 dark:bg-cyan-950/40 dark:text-cyan-300' }}">
                        📤 Sent
                    </button>
                    <button wire:click="setStatusFilter('failed')"
                        class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $statusFilter === 'failed' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-950/40 dark:text-red-300' }}">
                        ✗ Failed
                    </button>
                </div>

                {{-- Type & Source Pills --}}
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mr-1">Type:</span>
                    <button wire:click="setTypeFilter(null)"
                        class="px-2 py-0.5 rounded text-[11px] font-medium transition-colors {{ !$typeFilter ? 'bg-gray-800 text-white dark:bg-gray-200 dark:text-gray-900' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        All Types
                    </button>
                    <button wire:click="setTypeFilter('quick')"
                        class="px-2 py-0.5 rounded text-[11px] font-medium transition-colors {{ $typeFilter === 'quick' ? 'bg-amber-600 text-white' : 'text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/30' }}">
                        ⚡ Quick SMS
                    </button>
                    <button wire:click="setTypeFilter('bulk')"
                        class="px-2 py-0.5 rounded text-[11px] font-medium transition-colors {{ $typeFilter === 'bulk' ? 'bg-purple-600 text-white' : 'text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-950/30' }}">
                        📦 Bulk Batch
                    </button>

                    <span class="text-gray-300 dark:text-gray-700 mx-1">|</span>

                    <button wire:click="setSourceFilter('api')"
                        class="px-2 py-0.5 rounded text-[11px] font-medium transition-colors {{ $sourceFilter === 'api' ? 'bg-indigo-600 text-white' : 'text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/30' }}">
                        API
                    </button>
                    <button wire:click="setSourceFilter('web')"
                        class="px-2 py-0.5 rounded text-[11px] font-medium transition-colors {{ $sourceFilter === 'web' ? 'bg-gray-600 text-white' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        Web
                    </button>
                </div>
            </div>
        </div>

        {{-- Main Feed Body --}}
        @if($viewMode === 'stream')
            {{-- Narrative Live Action Stream Cards --}}
            <div class="divide-y divide-gray-100 dark:divide-gray-800 max-h-[640px] overflow-y-auto">
                @forelse($messages as $msg)
                    @php
                        $statusBadge = match($msg['status']) {
                            'pending' => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800', 'dot' => 'bg-amber-500', 'label' => 'PENDING', 'pulse' => true],
                            'sending' => ['bg' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800', 'dot' => 'bg-blue-500', 'label' => 'SENDING', 'pulse' => true],
                            'sent' => ['bg' => 'bg-cyan-50 text-cyan-700 border-cyan-200 dark:bg-cyan-950/40 dark:text-cyan-400 dark:border-cyan-800', 'dot' => 'bg-cyan-500', 'label' => 'SENT', 'pulse' => false],
                            'delivered' => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800', 'dot' => 'bg-emerald-500', 'label' => 'DELIVERED', 'pulse' => false],
                            'failed' => ['bg' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-800', 'dot' => 'bg-red-500', 'label' => 'FAILED', 'pulse' => false],
                            'dlr_failed' => ['bg' => 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/40 dark:text-orange-400 dark:border-orange-800', 'dot' => 'bg-orange-500', 'label' => 'DLR FAILED', 'pulse' => false],
                            default => ['bg' => 'bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700', 'dot' => 'bg-gray-400', 'label' => strtoupper($msg['status']), 'pulse' => false],
                        };
                        $isSendingOrPending = in_array($msg['status'], ['pending', 'sending']);
                    @endphp

                    <div class="p-4 hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors {{ $isSendingOrPending ? 'bg-blue-50/20 dark:bg-blue-950/10' : '' }}"
                         x-data="{ expanded: false }">
                        <div class="flex flex-col md:flex-row md:items-start justify-between gap-3">
                            {{-- Left Action & Narrative --}}
                            <div class="flex items-start gap-3 min-w-0 flex-1">
                                {{-- Customer Avatar / Icon --}}
                                <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xs shadow-sm">
                                    {{ strtoupper(substr($msg['customer_name'], 0, 2)) }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    {{-- Narrative Sentence: "[Customer] is sending [message] via [gateway] to [recipient]" --}}
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                                        <span class="font-bold text-gray-950 dark:text-white">
                                            {{ $msg['customer_name'] }}
                                        </span>

                                        @if($msg['customer_business'])
                                            <span class="text-xs text-gray-400 font-normal">({{ $msg['customer_business'] }})</span>
                                        @endif

                                        <span class="text-xs px-1.5 py-0.5 rounded font-medium {{ $msg['is_bulk'] ? 'bg-purple-100 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' }}">
                                            {{ $msg['action_type'] }}
                                            @if($msg['is_bulk'])
                                                #{{ $msg['batch_id'] }}
                                            @endif
                                        </span>

                                        <span class="text-xs text-gray-400">sending to</span>

                                        <span class="font-mono font-bold text-gray-900 dark:text-white text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                            {{ $msg['recipient'] }}
                                        </span>

                                        <span class="text-xs text-gray-400">via</span>

                                        <span class="inline-flex items-center gap-1 font-semibold text-xs text-emerald-600 dark:text-emerald-400">
                                            <x-heroicon-o-server class="w-3.5 h-3.5" />
                                            {{ $msg['gateway_name'] }}
                                            <span class="font-mono text-[10px] text-gray-400">({{ $msg['gateway_driver'] }})</span>
                                        </span>
                                    </div>

                                    {{-- The Message Preview Box --}}
                                    <div class="mt-2 p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700/60 text-xs text-gray-700 dark:text-gray-200 font-sans">
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="leading-relaxed cursor-pointer"
                                               x-on:click="expanded = !expanded"
                                               x-text="expanded ? @js($msg['body']) : @js(mb_substr($msg['body'], 0, 150) . (mb_strlen($msg['body']) > 150 ? ' ... (click to read more)' : ''))">
                                            </p>
                                        </div>

                                        @if($msg['failed_reason'])
                                            <div class="mt-2 flex items-center gap-1.5 text-xs text-red-600 dark:text-red-400 font-mono font-medium bg-red-50 dark:bg-red-950/40 p-1.5 rounded border border-red-200 dark:border-red-900">
                                                <x-heroicon-o-exclamation-circle class="w-4 h-4 flex-shrink-0" />
                                                <span>{{ $msg['failed_reason'] }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Metadata Pills Row --}}
                                    <div class="flex flex-wrap items-center gap-2 mt-2 text-[11px]">
                                        {{-- Sender ID --}}
                                        <span class="inline-flex items-center gap-1 text-gray-600 dark:text-gray-300">
                                            <span class="text-gray-400">Sender:</span>
                                            <span class="font-mono font-semibold">{{ $msg['sender_id'] }}</span>
                                            <span class="text-[10px] px-1 rounded {{ $msg['is_masking'] ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400' : 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400' }}">
                                                {{ $msg['is_masking'] ? 'Masking' : 'Non-masking' }}
                                            </span>
                                        </span>

                                        <span class="text-gray-300 dark:text-gray-700">•</span>

                                        {{-- Operator --}}
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border text-[11px] font-medium {{ $msg['operator_color'] }}">
                                            <x-heroicon-o-signal class="w-3 h-3" />
                                            {{ $msg['operator'] }}
                                        </span>

                                        <span class="text-gray-300 dark:text-gray-700">•</span>

                                        {{-- Tariff & Rate --}}
                                        <span class="text-gray-500 dark:text-gray-400">
                                            {{ $msg['tariff_plan'] }}
                                            @if($msg['rate'] > 0)
                                                <span class="font-mono font-medium text-gray-700 dark:text-gray-300">(৳{{ number_format($msg['rate'], 4) }}/msg)</span>
                                            @endif
                                        </span>

                                        <span class="text-gray-300 dark:text-gray-700">•</span>

                                        {{-- Cost --}}
                                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                                            Cost: ৳{{ number_format($msg['cost'], 4) }}
                                        </span>

                                        <span class="text-gray-300 dark:text-gray-700">•</span>

                                        {{-- Parts & Encoding --}}
                                        <span class="text-gray-500 dark:text-gray-400">
                                            {{ $msg['parts'] }} {{ $msg['parts'] > 1 ? 'parts' : 'part' }}
                                            @if($msg['is_unicode'])
                                                <span class="text-purple-600 dark:text-purple-400 font-medium">(Unicode)</span>
                                            @else
                                                <span class="text-gray-400">(GSM-7)</span>
                                            @endif
                                        </span>

                                        <span class="text-gray-300 dark:text-gray-700">•</span>

                                        {{-- Source --}}
                                        <span class="text-gray-400">
                                            Source: <span class="font-medium text-gray-600 dark:text-gray-300 uppercase">{{ $msg['source'] }}</span>
                                            @if($msg['source_ip'])
                                                <span class="font-mono text-[10px]">({{ $msg['source_ip'] }})</span>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Right Status & Timing --}}
                            <div class="flex md:flex-col items-end justify-between md:justify-start gap-2 flex-shrink-0 self-stretch md:self-auto pt-1">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border {{ $statusBadge['bg'] }}">
                                    <span class="relative flex h-2 w-2">
                                        @if($statusBadge['pulse'])
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $statusBadge['dot'] }} opacity-75"></span>
                                        @endif
                                        <span class="relative inline-flex rounded-full h-2 w-2 {{ $statusBadge['dot'] }}"></span>
                                    </span>
                                    {{ $statusBadge['label'] }}
                                </span>

                                <div class="text-right">
                                    <span class="block text-xs font-mono font-medium text-gray-900 dark:text-white">{{ $msg['timestamp'] }}</span>
                                    <span class="block text-[10px] text-gray-400">{{ $msg['time_ago'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center text-gray-400 dark:text-gray-500">
                        <x-heroicon-o-inbox class="w-10 h-10 mx-auto mb-2 opacity-50" />
                        <p class="text-sm font-medium">No live actions found</p>
                        <p class="text-xs mt-1">Waiting for incoming SMS traffic or check your active filters...</p>
                    </div>
                @endforelse
            </div>
        @else
            {{-- Dense Grid Table View --}}
            <div class="overflow-x-auto max-h-[640px] overflow-y-auto">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 bg-gray-50 dark:bg-gray-800/90 backdrop-blur border-b border-gray-200 dark:border-gray-700 z-10">
                        <tr>
                            <th class="px-3 py-2.5 font-semibold text-gray-600 dark:text-gray-300">Time</th>
                            <th class="px-3 py-2.5 font-semibold text-gray-600 dark:text-gray-300">Status</th>
                            <th class="px-3 py-2.5 font-semibold text-gray-600 dark:text-gray-300">Customer</th>
                            <th class="px-3 py-2.5 font-semibold text-gray-600 dark:text-gray-300">Type</th>
                            <th class="px-3 py-2.5 font-semibold text-gray-600 dark:text-gray-300">Sender / CLI</th>
                            <th class="px-3 py-2.5 font-semibold text-gray-600 dark:text-gray-300">Recipient</th>
                            <th class="px-3 py-2.5 font-semibold text-gray-600 dark:text-gray-300">Content</th>
                            <th class="px-3 py-2.5 font-semibold text-gray-600 dark:text-gray-300">Gateway</th>
                            <th class="px-3 py-2.5 font-semibold text-gray-600 dark:text-gray-300">Operator</th>
                            <th class="px-3 py-2.5 font-semibold text-gray-600 dark:text-gray-300">Tariff Plan</th>
                            <th class="px-3 py-2.5 font-semibold text-gray-600 dark:text-gray-300">Cost</th>
                            <th class="px-3 py-2.5 font-semibold text-gray-600 dark:text-gray-300">Parts</th>
                            <th class="px-3 py-2.5 font-semibold text-gray-600 dark:text-gray-300">Source</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($messages as $msg)
                            @php
                                $statusBadge = match($msg['status']) {
                                    'pending' => ['bg' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400', 'label' => 'PENDING'],
                                    'sending' => ['bg' => 'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400', 'label' => 'SENDING'],
                                    'sent' => ['bg' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-400', 'label' => 'SENT'],
                                    'delivered' => ['bg' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400', 'label' => 'DELIVERED'],
                                    'failed' => ['bg' => 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400', 'label' => 'FAILED'],
                                    'dlr_failed' => ['bg' => 'bg-orange-100 text-orange-700 dark:bg-orange-950/40 dark:text-orange-400', 'label' => 'DLR FAIL'],
                                    default => ['bg' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300', 'label' => strtoupper($msg['status'])],
                                };
                            @endphp
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">
                                <td class="px-3 py-2 whitespace-nowrap font-mono text-gray-500">
                                    {{ $msg['timestamp'] }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold {{ $statusBadge['bg'] }}">
                                        {{ $statusBadge['label'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                                    {{ $msg['customer_name'] }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span class="text-[10px] px-1.5 py-0.5 rounded font-medium {{ $msg['is_bulk'] ? 'bg-purple-100 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' }}">
                                        {{ $msg['action_type'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap font-mono">
                                    {{ $msg['sender_id'] }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap font-mono font-medium text-gray-900 dark:text-white">
                                    {{ $msg['recipient'] }}
                                </td>
                                <td class="px-3 py-2 max-w-xs truncate text-gray-600 dark:text-gray-300" title="{{ $msg['body'] }}">
                                    {{ $msg['body'] }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap font-medium text-emerald-600 dark:text-emerald-400">
                                    {{ $msg['gateway_name'] }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] border {{ $msg['operator_color'] }}">
                                        {{ $msg['operator'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-gray-500">
                                    {{ $msg['tariff_plan'] }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                    ৳{{ number_format($msg['cost'], 4) }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-center font-mono">
                                    {{ $msg['parts'] }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap uppercase text-gray-500">
                                    {{ $msg['source'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="p-8 text-center text-gray-400">
                                    No records found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Footer Status Bar --}}
        <div class="px-4 py-2.5 border-t border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/40 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
            <div class="flex items-center gap-2">
                <span>Showing latest {{ count($messages) }} actions</span>
                <span class="text-gray-300 dark:text-gray-700">•</span>
                <span class="font-medium text-emerald-600 dark:text-emerald-400">
                    Live poll: 2s
                </span>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-[11px] text-gray-400">Max limit:</span>
                @foreach([10, 20, 50, 100] as $lim)
                    <button
                        type="button"
                        wire:click="setLimit({{ $lim }})"
                        class="px-2 py-0.5 rounded text-xs font-mono transition-colors {{ $limit === $lim ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400' }}"
                    >
                        {{ $lim }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>
