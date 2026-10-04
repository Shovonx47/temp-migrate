@php
    $messages = $this->getMessages();
    $activeBatches = $this->getActiveBatches();
    $customers = $this->getCustomersList();
    $pollInterval = $isPaused ? null : '3s';
@endphp

<div @if($pollInterval) wire:poll.{{ $pollInterval }} @endif class="w-full space-y-4">

    {{-- ============================================================ --}}
    {{-- ACTIVE BULK BATCHES MONITOR (shown when bulk batches exist)  --}}
    {{-- ============================================================ --}}
    @if($activeBatches->isNotEmpty())
        <div class="rounded-xl bg-white shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="flex items-center justify-center w-7 h-7 rounded-lg bg-amber-500/10 dark:bg-amber-500/20">
                            <x-heroicon-o-server-stack class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Active Customers' Bulk Batches</h3>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500">Live processing bulk SMS campaigns</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                        </span>
                        <span class="text-[10px] font-semibold text-amber-600 dark:text-amber-400">ACTIVE BATCHES</span>
                    </div>
                </div>
            </div>

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
                        $batchIsActive = in_array($batch['status'], ['pending', 'processing'], true);
                    @endphp
                    <div class="px-4 py-3 hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors {{ $batchIsActive ? 'border-l-2 border-l-amber-500' : '' }}">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-[10px] font-mono text-gray-400 flex-shrink-0">#{{ $batch['id'] }}</span>
                                <span class="inline-flex items-center gap-1 font-semibold text-xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800">
                                    👤 {{ $batch['customer_name'] }}
                                </span>
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

                        <div class="w-full h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden mb-2">
                            <div class="h-full rounded-full transition-all duration-700 {{ $batchStatusColors['bar'] }} {{ $batchIsActive ? 'animate-pulse' : '' }}"
                                 style="width: {{ min($batch['percent'], 100) }}%"></div>
                        </div>

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
    {{-- MAIN LIVE MESSAGE STREAM CARD                                --}}
    {{-- ============================================================ --}}
    <div class="rounded-xl bg-white shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">

        {{-- Header Bar --}}
        <div class="p-3.5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">

                {{-- Title & Live Status --}}
                <div class="flex items-center gap-2.5">
                    <div class="relative flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-500/10 dark:bg-emerald-500/20">
                        <x-heroicon-o-bolt class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                        @if(!$isPaused)
                            <span class="absolute -top-0.5 -right-0.5 flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                        @endif
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Customers Live Action Stream</h2>
                            @if($isPaused)
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">PAUSED</span>
                            @else
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">LIVE (3s)</span>
                            @endif
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Real-time dispatches from your registered customers</p>
                    </div>
                </div>

                {{-- Toolbar Actions --}}
                <div class="flex flex-wrap items-center gap-2">

                    {{-- Customer Filter Dropdown --}}
                    @if($customers->isNotEmpty())
                        <div class="relative">
                            <select
                                wire:model.live="selectedCustomerId"
                                class="h-8 rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs font-medium text-gray-700 dark:text-gray-200 py-1 pl-2.5 pr-8 focus:ring-amber-500 focus:border-amber-500 shadow-xs"
                            >
                                <option value="">👥 All Customers ({{ $customers->count() }})</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}{{ $c->business_name ? " ({$c->business_name})" : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Stale Purge Button --}}
                    <button
                        type="button"
                        wire:click="purgeStaleQueue"
                        wire:loading.attr="disabled"
                        title="Purge items stuck in queue for more than 8 minutes"
                        class="inline-flex items-center gap-1.5 h-8 px-2.5 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800 transition-colors shadow-xs"
                    >
                        <x-heroicon-m-trash class="w-3.5 h-3.5 text-rose-500" />
                        <span>Purge Stale (>8m)</span>
                    </button>

                    {{-- Requeue Stuck In-Flight --}}
                    <button
                        type="button"
                        wire:click="requeueStuck"
                        wire:loading.attr="disabled"
                        title="Reset in-flight sending dispatches that stalled without progress"
                        class="inline-flex items-center gap-1.5 h-8 px-2.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800 transition-colors shadow-xs"
                    >
                        <x-heroicon-m-arrow-path class="w-3.5 h-3.5 text-amber-600" />
                        <span>Requeue Stuck</span>
                    </button>

                    {{-- Pause / Resume --}}
                    <button
                        type="button"
                        wire:click="togglePause"
                        class="inline-flex items-center gap-1 h-8 px-2.5 rounded-lg text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors shadow-xs"
                    >
                        @if($isPaused)
                            <x-heroicon-m-play class="w-3.5 h-3.5 text-emerald-600" />
                            <span>Resume</span>
                        @else
                            <x-heroicon-m-pause class="w-3.5 h-3.5 text-amber-600" />
                            <span>Pause</span>
                        @endif
                    </button>

                    {{-- View Mode Toggle --}}
                    <div class="inline-flex rounded-lg p-0.5 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-xs">
                        <button
                            type="button"
                            wire:click="setViewMode('stream')"
                            class="px-2 py-1 rounded-md text-xs font-medium transition-colors {{ $viewMode === 'stream' ? 'bg-white text-gray-900 shadow-xs dark:bg-gray-700 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}"
                            title="Card Stream View"
                        >
                            <x-heroicon-m-queue-list class="w-3.5 h-3.5" />
                        </button>
                        <button
                            type="button"
                            wire:click="setViewMode('table')"
                            class="px-2 py-1 rounded-md text-xs font-medium transition-colors {{ $viewMode === 'table' ? 'bg-white text-gray-900 shadow-xs dark:bg-gray-700 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}"
                            title="Table View"
                        >
                            <x-heroicon-m-table-cells class="w-3.5 h-3.5" />
                        </button>
                    </div>

                </div>

            </div>

            {{-- Filter & Search Toolbar --}}
            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 flex flex-wrap items-center justify-between gap-2.5">

                {{-- Status Filter Pills --}}
                <div class="flex flex-wrap items-center gap-1.5">
                    <button
                        type="button"
                        wire:click="setStatusFilter(null)"
                        class="px-2.5 py-1 rounded-full text-xs font-medium transition-colors {{ is_null($statusFilter) ? 'bg-amber-600 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                    >
                        Live Activity
                    </button>
                    <button
                        type="button"
                        wire:click="setStatusFilter('pending')"
                        class="px-2.5 py-1 rounded-full text-xs font-medium transition-colors {{ $statusFilter === 'pending' ? 'bg-amber-500 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                    >
                        Pending
                    </button>
                    <button
                        type="button"
                        wire:click="setStatusFilter('sending')"
                        class="px-2.5 py-1 rounded-full text-xs font-medium transition-colors {{ $statusFilter === 'sending' ? 'bg-blue-600 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                    >
                        In-Flight (Sending)
                    </button>
                    <button
                        type="button"
                        wire:click="setStatusFilter('dispatched')"
                        class="px-2.5 py-1 rounded-full text-xs font-medium transition-colors {{ $statusFilter === 'dispatched' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                    >
                        Dispatched
                    </button>
                    <button
                        type="button"
                        wire:click="setStatusFilter('failed')"
                        class="px-2.5 py-1 rounded-full text-xs font-medium transition-colors {{ $statusFilter === 'failed' ? 'bg-rose-600 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                    >
                        Failed
                    </button>

                    <div class="h-4 w-px bg-gray-200 dark:bg-gray-700 mx-1"></div>

                    {{-- Type Filter --}}
                    <button
                        type="button"
                        wire:click="setTypeFilter(null)"
                        class="px-2 py-0.5 rounded text-[11px] font-medium transition-colors {{ is_null($typeFilter) ? 'bg-gray-800 text-white dark:bg-white dark:text-gray-900' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400' }}"
                    >
                        All Types
                    </button>
                    <button
                        type="button"
                        wire:click="setTypeFilter('quick')"
                        class="px-2 py-0.5 rounded text-[11px] font-medium transition-colors {{ $typeFilter === 'quick' ? 'bg-gray-800 text-white dark:bg-white dark:text-gray-900' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400' }}"
                    >
                        Quick
                    </button>
                    <button
                        type="button"
                        wire:click="setTypeFilter('bulk')"
                        class="px-2 py-0.5 rounded text-[11px] font-medium transition-colors {{ $typeFilter === 'bulk' ? 'bg-gray-800 text-white dark:bg-white dark:text-gray-900' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400' }}"
                    >
                        Bulk
                    </button>
                </div>

                {{-- Search & Limit --}}
                <div class="flex items-center gap-2">
                    <div class="relative w-56">
                        <x-heroicon-m-magnifying-glass class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" />
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Recipient, text, customer..."
                            class="w-full h-8 pl-8 pr-2 text-xs rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder:text-gray-400 focus:ring-amber-500 focus:border-amber-500 shadow-xs"
                        />
                    </div>

                    <select
                        wire:model.live="limit"
                        class="h-8 rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-300 py-1 pl-2 pr-6 focus:ring-amber-500 focus:border-amber-500 shadow-xs"
                    >
                        <option value="20">20</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

            </div>

        </div>

        {{-- Stream Content --}}
        @if($messages->isEmpty())
            <div class="p-12 text-center">
                <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-3">
                    <x-heroicon-o-inbox class="w-6 h-6 text-gray-400" />
                </div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">No Live Queue Activity</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                    @if($search || $statusFilter || $typeFilter || $selectedCustomerId)
                        No messages match your active filters. Try clearing the search or changing status filter.
                    @else
                        Your customers currently have no in-flight or pending SMS actions in the pipeline.
                    @endif
                </p>
            </div>
        @elseif($viewMode === 'table')
            {{-- COMPACT TABLE VIEW --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50/75 dark:bg-gray-800/50 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
                        <tr>
                            <th class="px-3 py-2.5">ID</th>
                            <th class="px-3 py-2.5">Customer</th>
                            <th class="px-3 py-2.5">Type</th>
                            <th class="px-3 py-2.5">Sender</th>
                            <th class="px-3 py-2.5">Recipient / Operator</th>
                            <th class="px-3 py-2.5">Message Body</th>
                            <th class="px-3 py-2.5">Gateway</th>
                            <th class="px-3 py-2.5">Cost</th>
                            <th class="px-3 py-2.5">Status</th>
                            <th class="px-3 py-2.5">Time</th>
                            <th class="px-3 py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($messages as $msg)
                            @php
                                $statusStyle = match($msg['status']) {
                                    'pending'    => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                    'sending'    => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                    'sent'       => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                    'delivered'  => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                    'failed', 'dlr_failed' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
                                    default      => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition-colors {{ $msg['is_stale'] ? 'bg-rose-50/30 dark:bg-rose-950/10' : '' }}">
                                <td class="px-3 py-2 font-mono text-[11px] text-gray-500">#{{ $msg['id'] }}</td>
                                <td class="px-3 py-2">
                                    <div class="font-semibold text-gray-900 dark:text-white truncate max-w-[140px]">{{ $msg['customer_name'] }}</div>
                                    @if($msg['customer_business'])
                                        <div class="text-[10px] text-gray-400 truncate max-w-[140px]">{{ $msg['customer_business'] }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium {{ $msg['is_bulk'] ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300' }}">
                                        {{ $msg['is_bulk'] ? 'Bulk' : 'Quick' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 font-mono text-[11px] text-gray-700 dark:text-gray-300">{{ $msg['sender_id'] }}</td>
                                <td class="px-3 py-2">
                                    <div class="font-mono text-gray-900 dark:text-white font-medium">{{ $msg['recipient'] }}</div>
                                    <span class="inline-block mt-0.5 px-1 rounded text-[9px] font-medium border {{ $msg['operator_color'] }}">
                                        {{ $msg['operator'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 max-w-[240px]">
                                    <p class="truncate text-gray-600 dark:text-gray-300" title="{{ $msg['body'] }}">
                                        {{ $msg['body'] }}
                                    </p>
                                    @if($msg['failed_reason'])
                                        <p class="text-[10px] text-rose-600 dark:text-rose-400 truncate" title="{{ $msg['failed_reason'] }}">
                                            ⚠️ {{ $msg['failed_reason'] }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-gray-500 font-mono text-[11px]">{{ $msg['gateway_name'] }}</td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300 font-mono">৳{{ number_format($msg['cost'], 2) }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase {{ $statusStyle }}">
                                        {{ $msg['status'] }}
                                    </span>
                                    @if($msg['is_stale'])
                                        <div class="text-[9px] font-bold text-rose-600 mt-0.5">⚠️ STUCK >8M</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-gray-400 text-[11px] whitespace-nowrap">{{ $msg['time_ago'] }}</td>
                                <td class="px-3 py-2 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        @if(in_array($msg['status'], ['failed', 'dlr_failed'], true) || $msg['is_stale'])
                                            <button
                                                type="button"
                                                wire:click="retrySingleMessage({{ $msg['id'] }})"
                                                class="p-1 rounded text-amber-600 hover:bg-amber-50 dark:hover:bg-gray-800 transition-colors"
                                                title="Retry this message"
                                            >
                                                <x-heroicon-m-arrow-path class="w-3.5 h-3.5" />
                                            </button>
                                        @endif
                                        @if(in_array($msg['status'], ['pending', 'sending'], true))
                                            <button
                                                type="button"
                                                wire:click="deleteMessage({{ $msg['id'] }})"
                                                class="p-1 rounded text-rose-600 hover:bg-rose-50 dark:hover:bg-gray-800 transition-colors"
                                                title="Cancel & purge from queue"
                                            >
                                                <x-heroicon-m-x-mark class="w-3.5 h-3.5" />
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            {{-- CARD STREAM VIEW --}}
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($messages as $msg)
                    @php
                        $statusStyle = match($msg['status']) {
                            'pending'    => ['badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', 'border' => 'border-l-amber-400'],
                            'sending'    => ['badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',    'border' => 'border-l-blue-500'],
                            'sent'       => ['badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', 'border' => 'border-l-emerald-500'],
                            'delivered'  => ['badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', 'border' => 'border-l-emerald-500'],
                            'failed', 'dlr_failed' => ['badge' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400', 'border' => 'border-l-rose-500'],
                            default      => ['badge' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400', 'border' => 'border-l-gray-300'],
                        };
                    @endphp
                    <div class="p-3.5 border-l-4 {{ $statusStyle['border'] }} hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors {{ $msg['is_stale'] ? 'bg-rose-50/20 dark:bg-rose-950/10' : '' }}">
                        <div class="flex items-start justify-between gap-3">

                            {{-- Left Block: Customer + Recipient + Message --}}
                            <div class="space-y-1.5 min-w-0 flex-1">

                                {{-- Meta Row --}}
                                <div class="flex flex-wrap items-center gap-2">
                                    {{-- Customer badge --}}
                                    <span class="inline-flex items-center gap-1 font-semibold text-xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800 shadow-2xs">
                                        👤 {{ $msg['customer_name'] }}
                                        @if($msg['customer_business'])
                                            <span class="opacity-75 text-[10px]">({{ $msg['customer_business'] }})</span>
                                        @endif
                                    </span>

                                    {{-- Action type badge --}}
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $msg['is_bulk'] ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300' }}">
                                        {{ $msg['action_type'] }}
                                        @if($msg['batch_id'])
                                            <span class="ml-1 opacity-75">#{{ $msg['batch_id'] }}</span>
                                        @endif
                                    </span>

                                    {{-- Sender ID --}}
                                    <span class="text-xs font-mono font-medium text-gray-700 dark:text-gray-300">
                                        {{ $msg['sender_id'] }}
                                        @if($msg['is_masking'])
                                            <span class="text-[9px] px-1 py-0.2 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 font-sans">Masking</span>
                                        @endif
                                    </span>

                                    <span class="text-gray-300 dark:text-gray-700">→</span>

                                    {{-- Recipient Phone --}}
                                    <span class="text-xs font-mono font-bold text-gray-900 dark:text-white">
                                        {{ $msg['recipient'] }}
                                    </span>

                                    {{-- Operator Badge --}}
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold border {{ $msg['operator_color'] }}">
                                        {{ $msg['operator'] }}
                                    </span>

                                    {{-- Gateway Name --}}
                                    <span class="text-[10px] text-gray-400 font-mono">
                                        via {{ $msg['gateway_name'] }}
                                    </span>
                                </div>

                                {{-- Message Body --}}
                                <div class="text-xs text-gray-700 dark:text-gray-200 font-sans leading-relaxed break-words bg-gray-50/70 dark:bg-gray-800/50 p-2 rounded-lg border border-gray-100 dark:border-gray-800">
                                    {{ $msg['body'] }}
                                </div>

                                {{-- Failure Reason Alert if present --}}
                                @if($msg['failed_reason'])
                                    <div class="flex items-center gap-1.5 text-xs text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 p-1.5 rounded border border-rose-200 dark:border-rose-900 font-mono">
                                        <x-heroicon-m-exclamation-triangle class="w-3.5 h-3.5 flex-shrink-0" />
                                        <span>{{ $msg['failed_reason'] }}</span>
                                    </div>
                                @endif

                            </div>

                            {{-- Right Block: Status, Cost, Actions --}}
                            <div class="flex flex-col items-end gap-1.5 flex-shrink-0">

                                {{-- Status pill --}}
                                <div class="flex items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $statusStyle['badge'] }}">
                                        @if(in_array($msg['status'], ['pending', 'sending'], true))
                                            <span class="relative flex h-1.5 w-1.5">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-amber-500"></span>
                                            </span>
                                        @endif
                                        {{ $msg['status'] }}
                                    </span>

                                    @if($msg['is_stale'])
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-600 text-white animate-pulse">
                                            STUCK >8M
                                        </span>
                                    @endif
                                </div>

                                {{-- Rate & Cost --}}
                                <div class="text-xs font-mono text-gray-900 dark:text-white font-bold">
                                    ৳{{ number_format($msg['cost'], 2) }}
                                    <span class="text-[10px] text-gray-400 font-normal">({{ $msg['parts'] }} {{ \Illuminate\Support\Str::plural('part', $msg['parts']) }})</span>
                                </div>

                                {{-- Timestamp --}}
                                <div class="text-[11px] text-gray-400" title="{{ $msg['timestamp'] }}">
                                    {{ $msg['time_ago'] }}
                                </div>

                                {{-- Action Buttons --}}
                                <div class="flex items-center gap-1.5 pt-1">
                                    @if(in_array($msg['status'], ['failed', 'dlr_failed'], true) || $msg['is_stale'])
                                        <button
                                            type="button"
                                            wire:click="retrySingleMessage({{ $msg['id'] }})"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800 transition-colors shadow-2xs"
                                            title="Retry this message"
                                        >
                                            <x-heroicon-m-arrow-path class="w-3 h-3 text-amber-600" />
                                            <span>Retry</span>
                                        </button>
                                    @endif

                                    @if(in_array($msg['status'], ['pending', 'sending'], true))
                                        <button
                                            type="button"
                                            wire:click="deleteMessage({{ $msg['id'] }})"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800 transition-colors shadow-2xs"
                                            title="Cancel and remove from queue"
                                        >
                                            <x-heroicon-m-x-mark class="w-3 h-3 text-rose-600" />
                                            <span>Cancel</span>
                                        </button>
                                    @endif
                                </div>

                            </div>

                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>

</div>
