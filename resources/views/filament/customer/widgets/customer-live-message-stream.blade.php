@php
    $messages = $this->getMessages();
    $activeBatches = $this->getActiveBatches();
    $pollInterval = $isPaused ? null : '3s';
@endphp

<div @if($pollInterval) wire:poll.{{ $pollInterval }} @endif class="w-full space-y-4">

    {{-- ============================================================ --}}
    {{-- ACTIVE BULK BATCHES MONITOR (shown only when batches exist)  --}}
    {{-- ============================================================ --}}
    @if($activeBatches->isNotEmpty())
        <div class="rounded-xl bg-white shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
            {{-- Header --}}
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="flex items-center justify-center w-7 h-7 rounded-lg bg-cyan-500/10 dark:bg-cyan-500/20">
                            <x-heroicon-o-server-stack class="w-4 h-4 text-cyan-600 dark:text-cyan-400" />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Your Active Bulk Batches</h3>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500">Bulk SMS jobs currently processing</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-cyan-500"></span>
                        </span>
                        <span class="text-[10px] font-semibold text-cyan-600 dark:text-cyan-400">LIVE</span>
                    </div>
                </div>
            </div>

            {{-- Batch rows --}}
            <div class="divide-y divide-gray-50 dark:divide-gray-800">
                @foreach($activeBatches as $batch)
                    @php
                        $batchStatusColors = match($batch['status']) {
                            'pending'    => ['badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',   'bar' => 'bg-amber-500'],
                            'processing' => ['badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',      'bar' => 'bg-blue-500'],
                            'completed'  => ['badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', 'bar' => 'bg-emerald-500'],
                            'failed'     => ['badge' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',          'bar' => 'bg-red-500'],
                            default      => ['badge' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',          'bar' => 'bg-gray-400'],
                        };
                        $batchIsActive = in_array($batch['status'], ['pending', 'processing']);
                    @endphp
                    <div class="px-4 py-3 hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors {{ $batchIsActive ? 'border-l-2 border-l-blue-500' : '' }}">
                        {{-- Row header --}}
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-[10px] font-mono text-gray-400 flex-shrink-0">#{{ $batch['id'] }}</span>
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold {{ $batchStatusColors['badge'] }}">
                                    @if($batchIsActive)
                                        <span class="relative flex h-1.5 w-1.5">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $batchStatusColors['bar'] }} opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 {{ $batchStatusColors['bar'] }}"></span>
                                        </span>
                                    @endif
                                    {{ strtoupper($batch['status']) }}
                                </span>
                                <span class="font-mono text-[10px] text-gray-600 dark:text-gray-400">{{ $batch['sender_id'] }}</span>
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $batch['percent'] }}%</span>
                                <span class="text-[10px] text-gray-400">{{ $batch['time_ago'] }}</span>
                            </div>
                        </div>

                        {{-- Progress bar --}}
                        <div class="w-full h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden mb-2">
                            <div class="h-full rounded-full transition-all duration-700 {{ $batchStatusColors['bar'] }} {{ $batchIsActive ? 'animate-pulse' : '' }}"
                                 style="width: {{ min($batch['percent'], 100) }}%"></div>
                        </div>

                        {{-- Stats row --}}
                        <div class="flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400">
                            <span>{{ number_format($batch['processed']) }} of {{ number_format($batch['total_recipients']) }} recipients processed</span>
                            @if($batch['body'])
                                <span class="italic text-gray-400 dark:text-gray-500 truncate max-w-[260px]">"{{ $batch['body'] }}"</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- MAIN LIVE MESSAGE STREAM                                      --}}
    {{-- ============================================================ --}}
    <div class="rounded-xl bg-white shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">

        {{-- Header Bar --}}
        <div class="p-3.5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2.5">

                {{-- Title & Live Beacon --}}
                <div class="flex items-center gap-2.5">
                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400">
                        <x-heroicon-o-bolt class="w-4 h-4" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">My Live SMS Queue</h2>
                            @if(!$isPaused)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                    <span class="relative flex h-1.5 w-1.5">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                    </span>
                                    LIVE (3s)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                    <x-heroicon-o-pause class="w-2.5 h-2.5" />
                                    PAUSED
                                </span>
                            @endif
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            Real-time view of your SMS messages — pending, in-flight, and recently dispatched
                        </p>
                    </div>
                </div>

                {{-- Controls --}}
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Search --}}
                    <div class="relative min-w-[190px]">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search recipient or message..."
                            class="w-full text-xs rounded-lg border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 dark:text-white py-1 pl-7 pr-2.5 focus:border-emerald-500 focus:ring-emerald-500"
                        />
                        <x-heroicon-o-magnifying-glass class="w-3.5 h-3.5 text-gray-400 absolute left-2 top-2" />
                    </div>

                    {{-- View Mode Switch --}}
                    <div class="flex items-center rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 p-0.5">
                        <button
                            type="button"
                            wire:click="setViewMode('stream')"
                            class="px-2 py-1 text-xs font-medium rounded-md transition-all {{ $viewMode === 'stream' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-xs' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                        >
                            <span class="flex items-center gap-1">
                                <x-heroicon-o-list-bullet class="w-3.5 h-3.5" />
                                Stream
                            </span>
                        </button>
                        <button
                            type="button"
                            wire:click="setViewMode('table')"
                            class="px-2 py-1 text-xs font-medium rounded-md transition-all {{ $viewMode === 'table' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-xs' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                        >
                            <span class="flex items-center gap-1">
                                <x-heroicon-o-table-cells class="w-3.5 h-3.5" />
                                Table
                            </span>
                        </button>
                    </div>

                    {{-- Pause / Resume --}}
                    <button
                        type="button"
                        wire:click="togglePause"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border text-xs font-medium transition-colors
                            {{ $isPaused
                                ? 'bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300'
                                : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300' }}"
                    >
                        @if($isPaused)
                            <x-heroicon-o-play class="w-3.5 h-3.5" />
                            Resume
                        @else
                            <x-heroicon-o-pause class="w-3.5 h-3.5" />
                            Pause
                        @endif
                    </button>
                </div>
            </div>

            {{-- Filter Pills Bar --}}
            <div class="flex flex-wrap items-center justify-between gap-2 mt-2 pt-2 border-t border-gray-100 dark:border-gray-800 text-xs">
                {{-- Status Pills --}}
                <div class="flex flex-wrap items-center gap-1">
                    <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mr-1">Status:</span>
                    <button wire:click="setStatusFilter(null)"
                        class="px-2 py-0.5 rounded text-[11px] font-medium transition-colors {{ !$statusFilter ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400' }}">
                        All Live
                    </button>
                    <button wire:click="setStatusFilter('pending')"
                        class="px-2 py-0.5 rounded text-[11px] font-medium transition-colors {{ $statusFilter === 'pending' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300' }}">
                        ⏳ Pending
                    </button>
                    <button wire:click="setStatusFilter('sending')"
                        class="px-2 py-0.5 rounded text-[11px] font-medium transition-colors {{ $statusFilter === 'sending' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-300' }}">
                        ⚡ Sending
                    </button>
                    <button wire:click="setStatusFilter('dispatched')"
                        class="px-2 py-0.5 rounded text-[11px] font-medium transition-colors {{ $statusFilter === 'dispatched' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300' }}">
                        ✓ Dispatched
                    </button>
                </div>

                {{-- Type Pills --}}
                <div class="flex flex-wrap items-center gap-1">
                    <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mr-1">Type:</span>
                    <button wire:click="setTypeFilter(null)"
                        class="px-1.5 py-0.5 rounded text-[10px] font-medium transition-colors {{ !$typeFilter ? 'bg-gray-800 text-white dark:bg-gray-200 dark:text-gray-900' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        All
                    </button>
                    <button wire:click="setTypeFilter('quick')"
                        class="px-1.5 py-0.5 rounded text-[10px] font-medium transition-colors {{ $typeFilter === 'quick' ? 'bg-amber-600 text-white' : 'text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/30' }}">
                        ⚡ Quick SMS
                    </button>
                    <button wire:click="setTypeFilter('bulk')"
                        class="px-1.5 py-0.5 rounded text-[10px] font-medium transition-colors {{ $typeFilter === 'bulk' ? 'bg-purple-600 text-white' : 'text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-950/30' }}">
                        📦 Bulk SMS
                    </button>
                </div>
            </div>
        </div>

        {{-- ===== STREAM VIEW ===== --}}
        @if($viewMode === 'stream')
            <div class="divide-y divide-gray-100 dark:divide-gray-800 max-h-[620px] overflow-y-auto">
                @forelse($messages as $msg)
                    @php
                        $statusBadge = match($msg['status']) {
                            'pending'   => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800',   'dot' => 'bg-amber-500',   'label' => 'IN QUEUE',   'pulse' => true],
                            'sending'   => ['bg' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800',         'dot' => 'bg-blue-500',    'label' => 'SENDING',    'pulse' => true],
                            'sent'      => ['bg' => 'bg-cyan-50 text-cyan-700 border-cyan-200 dark:bg-cyan-950/40 dark:text-cyan-400 dark:border-cyan-800',         'dot' => 'bg-cyan-500',    'label' => 'DISPATCHED', 'pulse' => false],
                            'delivered' => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800', 'dot' => 'bg-emerald-500', 'label' => 'DELIVERED', 'pulse' => false],
                            'failed'    => ['bg' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-800',               'dot' => 'bg-red-500',     'label' => 'FAILED',     'pulse' => false],
                            'dlr_failed'=> ['bg' => 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/40 dark:text-orange-400 dark:border-orange-800', 'dot' => 'bg-orange-500', 'label' => 'DLR FAIL',  'pulse' => false],
                            default     => ['bg' => 'bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',             'dot' => 'bg-gray-400',    'label' => strtoupper($msg['status']), 'pulse' => false],
                        };
                        $isActive = in_array($msg['status'], ['pending', 'sending']);
                    @endphp

                    <div class="p-3 hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors {{ $isActive ? 'bg-amber-50/20 dark:bg-amber-950/10' : '' }}"
                         x-data="{ expanded: false }">
                        <div class="flex flex-col md:flex-row md:items-start justify-between gap-2.5">

                            {{-- Left: Action narrative --}}
                            <div class="flex items-start gap-2.5 min-w-0 flex-1">
                                {{-- Type icon --}}
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-xs shadow-xs
                                    {{ $msg['is_bulk'] ? 'bg-gradient-to-br from-purple-500 to-violet-600' : 'bg-gradient-to-br from-amber-500 to-orange-600' }}">
                                    @if($msg['is_bulk'])
                                        <x-heroicon-o-rectangle-stack class="w-4 h-4" />
                                    @else
                                        <x-heroicon-o-paper-airplane class="w-4 h-4" />
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    {{-- Narrative line --}}
                                    <div class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs">
                                        <span class="text-[10px] px-1.5 py-0.5 rounded font-medium {{ $msg['is_bulk'] ? 'bg-purple-100 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' }}">
                                            {{ $msg['action_type'] }}
                                            @if($msg['is_bulk'] && $msg['batch_id'])
                                                #{{ $msg['batch_id'] }}
                                            @endif
                                        </span>

                                        <span class="text-gray-400 text-[11px]">→</span>

                                        <span class="font-mono font-bold text-gray-900 dark:text-white text-[11px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                            {{ $msg['recipient'] }}
                                        </span>

                                        {{-- Operator badge --}}
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded border text-[10px] font-medium {{ $msg['operator_color'] }}">
                                            <x-heroicon-o-signal class="w-2.5 h-2.5" />
                                            {{ $msg['operator'] }}
                                        </span>

                                        <span class="text-gray-400 text-[11px]">via</span>

                                        <span class="inline-flex items-center gap-1 font-semibold text-[11px] text-emerald-600 dark:text-emerald-400">
                                            <x-heroicon-o-server class="w-3 h-3" />
                                            {{ $msg['gateway_name'] }}
                                        </span>
                                    </div>

                                    {{-- Batch progress bar (bulk only) --}}
                                    @if($msg['is_bulk'] && $msg['batch_progress'])
                                        @php $bp = $msg['batch_progress']; @endphp
                                        <div class="mt-1.5">
                                            <div class="flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400 mb-1">
                                                <span>Batch #{{ $bp['id'] }} progress</span>
                                                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $bp['processed'] }} / {{ $bp['total'] }} ({{ $bp['percent'] }}%)</span>
                                            </div>
                                            <div class="w-full h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                                @php
                                                    $bpBar = match($bp['status']) {
                                                        'processing' => 'bg-blue-500',
                                                        'completed'  => 'bg-emerald-500',
                                                        'failed'     => 'bg-red-500',
                                                        default      => 'bg-amber-500',
                                                    };
                                                @endphp
                                                <div class="{{ $bpBar }} h-full rounded-full transition-all duration-700 {{ in_array($bp['status'], ['pending','processing']) ? 'animate-pulse' : '' }}"
                                                     style="width: {{ min($bp['percent'], 100) }}%"></div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Message preview --}}
                                    <div class="mt-1.5 p-2 rounded-lg bg-gray-50 dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700/60 text-xs text-gray-700 dark:text-gray-200">
                                        <p class="leading-relaxed cursor-pointer"
                                           x-on:click="expanded = !expanded"
                                           x-text="expanded ? @js($msg['body']) : @js(mb_substr($msg['body'], 0, 140) . (mb_strlen($msg['body']) > 140 ? ' ... (click to expand)' : ''))">
                                        </p>

                                        @if($msg['failed_reason'])
                                            <div class="mt-1.5 flex items-center gap-1 text-[11px] text-red-600 dark:text-red-400 font-mono font-medium bg-red-50 dark:bg-red-950/40 p-1 rounded border border-red-200 dark:border-red-900">
                                                <x-heroicon-o-exclamation-circle class="w-3.5 h-3.5 flex-shrink-0" />
                                                <span>{{ $msg['failed_reason'] }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Meta pills row --}}
                                    <div class="flex flex-wrap items-center gap-1.5 mt-1.5 text-[10px]">
                                        {{-- Sender CLI --}}
                                        <span class="inline-flex items-center gap-1 text-gray-600 dark:text-gray-300">
                                            <span class="text-gray-400">CLI:</span>
                                            <span class="font-mono font-semibold">{{ $msg['sender_id'] }}</span>
                                            <span class="text-[9px] px-1 rounded {{ $msg['is_masking'] ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400' : 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400' }}">
                                                {{ $msg['is_masking'] ? 'Masking' : 'Non-masking' }}
                                            </span>
                                        </span>

                                        <span class="text-gray-300 dark:text-gray-700">•</span>

                                        {{-- Tariff --}}
                                        <span class="text-gray-500 dark:text-gray-400">
                                            {{ $msg['tariff_plan'] }}
                                            @if($msg['rate'] > 0)
                                                <span class="font-mono font-medium text-gray-700 dark:text-gray-300">(৳{{ number_format($msg['rate'], 4) }})</span>
                                            @endif
                                        </span>

                                        <span class="text-gray-300 dark:text-gray-700">•</span>

                                        {{-- Cost --}}
                                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                                            ৳{{ number_format($msg['cost'], 4) }}
                                        </span>

                                        <span class="text-gray-300 dark:text-gray-700">•</span>

                                        {{-- Parts --}}
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
                                        <span class="text-gray-400 uppercase font-medium">{{ $msg['source'] }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Right: Status badge + timestamp --}}
                            <div class="flex md:flex-col items-end justify-between md:justify-start gap-1.5 flex-shrink-0 self-stretch md:self-auto pt-0.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $statusBadge['bg'] }}">
                                    <span class="relative flex h-1.5 w-1.5">
                                        @if($statusBadge['pulse'])
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $statusBadge['dot'] }} opacity-75"></span>
                                        @endif
                                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 {{ $statusBadge['dot'] }}"></span>
                                    </span>
                                    {{ $statusBadge['label'] }}
                                </span>

                                <div class="text-right">
                                    <span class="block text-xs font-mono font-medium text-gray-900 dark:text-white">{{ $msg['timestamp'] }}</span>
                                    <span class="block text-[9px] text-gray-400 font-medium">{{ $msg['time_ago'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    {{-- Empty state --}}
                    <div class="py-14 px-6 text-center">
                        <div class="relative w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                            <span class="absolute w-full h-full rounded-full bg-emerald-500/10 animate-ping opacity-75"></span>
                            <span class="absolute w-11 h-11 rounded-full bg-emerald-500/20 animate-pulse"></span>
                            <div class="relative w-8 h-8 rounded-full bg-emerald-500/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                <x-heroicon-o-signal class="w-4 h-4 animate-pulse" />
                            </div>
                        </div>
                        <h3 class="text-xs font-bold text-gray-900 dark:text-white tracking-tight">Your queue is idle</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 max-w-sm mx-auto mt-1">
                            No messages are currently in your queue or in-flight. Send a Quick SMS or Bulk SMS batch to see live updates here.
                        </p>
                        <div class="inline-flex items-center gap-1.5 mt-4 px-2.5 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-[10px] text-gray-600 dark:text-gray-300 font-mono">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                            <span>Listening for your messages · polling every 3s</span>
                        </div>
                    </div>
                @endforelse
            </div>

        {{-- ===== TABLE VIEW ===== --}}
        @else
            <div class="overflow-x-auto max-h-[620px] overflow-y-auto">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 bg-gray-50 dark:bg-gray-800/90 backdrop-blur border-b border-gray-200 dark:border-gray-700 z-10">
                        <tr>
                            <th class="px-2.5 py-2 font-semibold text-gray-600 dark:text-gray-300">Time</th>
                            <th class="px-2.5 py-2 font-semibold text-gray-600 dark:text-gray-300">Status</th>
                            <th class="px-2.5 py-2 font-semibold text-gray-600 dark:text-gray-300">Type</th>
                            <th class="px-2.5 py-2 font-semibold text-gray-600 dark:text-gray-300">Sender</th>
                            <th class="px-2.5 py-2 font-semibold text-gray-600 dark:text-gray-300">Recipient</th>
                            <th class="px-2.5 py-2 font-semibold text-gray-600 dark:text-gray-300">Operator</th>
                            <th class="px-2.5 py-2 font-semibold text-gray-600 dark:text-gray-300">Message</th>
                            <th class="px-2.5 py-2 font-semibold text-gray-600 dark:text-gray-300">Gateway</th>
                            <th class="px-2.5 py-2 font-semibold text-gray-600 dark:text-gray-300">Parts</th>
                            <th class="px-2.5 py-2 font-semibold text-gray-600 dark:text-gray-300">Cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($messages as $msg)
                            @php
                                $tableBadge = match($msg['status']) {
                                    'pending'    => 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400',
                                    'sending'    => 'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400',
                                    'sent'       => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-400',
                                    'delivered'  => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400',
                                    'failed'     => 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400',
                                    'dlr_failed' => 'bg-orange-100 text-orange-700 dark:bg-orange-950/40 dark:text-orange-400',
                                    default      => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-2.5 py-2 font-mono text-[10px] text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $msg['timestamp'] }}</td>
                                <td class="px-2.5 py-2 whitespace-nowrap">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold {{ $tableBadge }}">
                                        {{ strtoupper($msg['status']) }}
                                    </span>
                                </td>
                                <td class="px-2.5 py-2 whitespace-nowrap">
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-medium {{ $msg['is_bulk'] ? 'bg-purple-100 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' }}">
                                        {{ $msg['action_type'] }}
                                    </span>
                                </td>
                                <td class="px-2.5 py-2 font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap text-[10px]">{{ $msg['sender_id'] }}</td>
                                <td class="px-2.5 py-2 font-mono font-semibold text-gray-900 dark:text-white whitespace-nowrap text-[10px]">{{ $msg['recipient'] }}</td>
                                <td class="px-2.5 py-2 whitespace-nowrap">
                                    <span class="px-1 py-0.5 rounded text-[9px] font-medium border {{ $msg['operator_color'] }}">{{ $msg['operator'] }}</span>
                                </td>
                                <td class="px-2.5 py-2 max-w-[200px] truncate text-gray-600 dark:text-gray-300 text-[11px]" title="{{ $msg['body'] }}">{{ $msg['body'] }}</td>
                                <td class="px-2.5 py-2 text-gray-700 dark:text-gray-300 whitespace-nowrap text-[11px]">{{ $msg['gateway_name'] }}</td>
                                <td class="px-2.5 py-2 text-gray-500 dark:text-gray-400 whitespace-nowrap text-[10px]">
                                    {{ $msg['parts'] }}{{ $msg['is_unicode'] ? ' U' : '' }}
                                </td>
                                <td class="px-2.5 py-2 font-mono font-semibold text-emerald-600 dark:text-emerald-400 whitespace-nowrap text-[10px]">
                                    ৳{{ number_format($msg['cost'], 4) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500 text-xs">
                                    <x-heroicon-o-signal class="w-6 h-6 mx-auto mb-1 opacity-50 animate-pulse" />
                                    <p>No messages in your live queue right now.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
