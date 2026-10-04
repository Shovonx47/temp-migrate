<x-filament-panels::page>
    @php
        $summary = $this->getSummaryData();
    @endphp

    <!-- Date Selector & Controls Section -->
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 transition-all">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between pb-4 border-b border-gray-100 dark:border-white/5">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-bold tracking-tight text-gray-950 dark:text-white">
                        Customer SMS Usage & Performance Report
                    </h2>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-primary-50 text-primary-700 dark:bg-primary-950/60 dark:text-primary-300 ring-1 ring-inset ring-primary-600/20">
                        <span class="h-1.5 w-1.5 rounded-full bg-primary-600 dark:bg-primary-400 animate-pulse"></span>
                        Total SMS: {{ number_format($summary['total_sms']) }}
                    </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $this->getTimeframeLabel() }}
                </p>
            </div>

            <!-- Date Range Controls & Reset -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1.5 rounded-xl bg-gray-50/80 p-1.5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="flex items-center gap-1">
                        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 pl-1">From:</span>
                        <input
                            type="date"
                            wire:model.live="fromDate"
                            class="block w-32 rounded-lg border-0 py-1 px-2 text-xs text-gray-900 bg-white ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-white/10"
                        />
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400">To:</span>
                        <input
                            type="date"
                            wire:model.live="toDate"
                            class="block w-32 rounded-lg border-0 py-1 px-2 text-xs text-gray-900 bg-white ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-white/10"
                        />
                    </div>
                </div>

                <button
                    type="button"
                    wire:click="resetReportFilters"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-900/50 transition-colors shadow-sm"
                    title="Reset all filters and date selection"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    Reset Filters
                </button>
            </div>
        </div>

        <!-- Date Presets Pill Bar -->
        <div class="mt-3.5 flex flex-wrap items-center gap-1.5 sm:gap-2">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 mr-1 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.253M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
                Presets:
            </span>

            @php
                $presets = [
                    'today' => 'Today',
                    'yesterday' => 'Yesterday',
                    'this_week' => 'This Week',
                    'last_week' => 'Last Week',
                    'this_month' => 'This Month',
                    'last_month' => 'Last Month',
                    'this_year' => 'This Year',
                    'all' => 'All Time',
                ];
            @endphp

            @foreach ($presets as $key => $label)
                <button
                    type="button"
                    wire:click="setDatePreset('{{ $key }}')"
                    class="px-3 py-1 rounded-lg text-xs font-semibold transition-all shadow-sm {{ $datePreset === $key ? 'bg-primary-600 text-white ring-1 ring-primary-500 shadow-primary-500/20' : 'bg-gray-100/80 hover:bg-gray-200/80 text-gray-700 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10 ring-1 ring-inset ring-gray-950/5 dark:ring-white/10' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- 8 Dynamic Summary Cards Grid -->
    <div class="mt-4 grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3">
        <!-- 1. Total Companies -->
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase">Companies</span>
                <span class="rounded-md bg-purple-50 p-1 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </span>
            </div>
            <div class="mt-2.5">
                <div class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {{ number_format($summary['total_companies']) }}
                </div>
                <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Active clients
                </div>
            </div>
        </div>

        <!-- 2. Total SMS Count -->
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase">Total SMS</span>
                <span class="rounded-md bg-blue-50 p-1 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2.5">
                <div class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {{ number_format($summary['total_sms']) }}
                </div>
                <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400 truncate" title="Masking: {{ number_format($summary['masking_count']) }} | Non-Masking: {{ number_format($summary['non_masking_count']) }}">
                    M: {{ number_format($summary['masking_count']) }} · NM: {{ number_format($summary['non_masking_count']) }}
                </div>
            </div>
        </div>

        <!-- 3. Delivered -->
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase">Delivered</span>
                <span class="rounded-md bg-emerald-50 p-1 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2.5">
                <div class="text-xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
                    {{ number_format($summary['delivered_count']) }}
                </div>
                <div class="mt-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400">
                    {{ $summary['delivery_rate'] }}% Delivery
                </div>
            </div>
        </div>

        <!-- 4. Failed -->
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase">Failed</span>
                <span class="rounded-md bg-rose-50 p-1 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2.5">
                <div class="text-xl font-bold tracking-tight {{ $summary['failed_count'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-950 dark:text-white' }}">
                    {{ number_format($summary['failed_count']) }}
                </div>
                <div class="mt-1 text-[11px] font-medium {{ $summary['failed_count'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-gray-400' }}">
                    {{ $summary['failure_rate'] }}% Failure
                </div>
            </div>
        </div>

        <!-- 5. Customer Spend -->
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase">Customer Spend</span>
                <span class="rounded-md bg-amber-50 p-1 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6H2.25m0 0v8.25m0 0a60.114 60.114 0 0015.797 2.101c.727.198 1.453-.342 1.453-1.096V6H20.25a.75.75 0 01-.75-.75V4.5m0 0A.75.75 0 0018.75 3.75h-13.5A.75.75 0 004.5 4.5m15 0H4.5" />
                    </svg>
                </span>
            </div>
            <div class="mt-2.5">
                <div class="text-lg font-bold tracking-tight text-gray-950 dark:text-white truncate" title="BDT {{ number_format($summary['customer_spend'], 2) }}">
                    BDT {{ number_format($summary['customer_spend'], 2) }}
                </div>
                <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Billed revenue
                </div>
            </div>
        </div>

        <!-- 6. Provider Cost -->
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase">Provider Cost</span>
                <span class="rounded-md bg-slate-100 p-1 text-slate-700 dark:bg-white/10 dark:text-slate-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.75 5.1a2.25 2.25 0 011.8-1.1h8.9a2.25 2.25 0 011.8 1.1l2.1 3.45a4.5 4.5 0 01.9 2.7" />
                    </svg>
                </span>
            </div>
            <div class="mt-2.5">
                <div class="text-lg font-bold tracking-tight text-gray-950 dark:text-white truncate" title="BDT {{ number_format($summary['provider_cost'], 2) }}">
                    BDT {{ number_format($summary['provider_cost'], 2) }}
                </div>
                <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Gateway & dipping
                </div>
            </div>
        </div>

        <!-- 7. Total Profit -->
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase">Total Profit</span>
                <span class="rounded-md {{ $summary['total_profit'] >= 0 ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400' : 'bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400' }} p-1">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                    </svg>
                </span>
            </div>
            <div class="mt-2.5">
                <div class="text-lg font-bold tracking-tight {{ $summary['total_profit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} truncate" title="BDT {{ number_format($summary['total_profit'], 2) }}">
                    BDT {{ number_format($summary['total_profit'], 2) }}
                </div>
                <div class="mt-1 text-[11px] font-medium {{ $summary['total_profit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    Margin: {{ $summary['margin_rate'] }}%
                </div>
            </div>
        </div>

        <!-- 8. Total Balance -->
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase">Total Balance</span>
                <span class="rounded-md bg-cyan-50 p-1 text-cyan-600 dark:bg-cyan-950/50 dark:text-cyan-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" />
                    </svg>
                </span>
            </div>
            <div class="mt-2.5">
                <div class="text-lg font-bold tracking-tight text-gray-950 dark:text-white truncate" title="BDT {{ number_format($summary['total_balance'], 2) }}">
                    BDT {{ number_format($summary['total_balance'], 2) }}
                </div>
                <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Customer wallets
                </div>
            </div>
        </div>
    </div>

    <!-- Profit & Performance Highlight Banner -->
    <div class="mt-3 rounded-xl p-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white/70 dark:bg-gray-900/70 ring-1 ring-gray-950/5 dark:ring-white/10 backdrop-blur-sm">
        <div class="flex items-center gap-2">
            <span class="inline-flex p-1.5 rounded-lg bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </span>
            <div>
                <span class="text-xs font-semibold text-gray-950 dark:text-white">
                    Profit Formula: Profit = Customer Spend − Provider Cost
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400 block sm:inline sm:ml-2">
                    (BDT {{ number_format($summary['customer_spend'], 2) }} − BDT {{ number_format($summary['provider_cost'], 2) }} = 
                    <strong class="{{ $summary['total_profit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                        BDT {{ number_format($summary['total_profit'], 2) }}
                    </strong>)
                </span>
            </div>
        </div>
        <div class="flex items-center gap-3 text-xs">
            <span class="inline-flex items-center gap-1 font-medium text-gray-600 dark:text-gray-300">
                Delivery Success: <strong class="text-emerald-600 dark:text-emerald-400">{{ $summary['delivery_rate'] }}%</strong>
            </span>
            <span class="inline-flex items-center gap-1 font-medium text-gray-600 dark:text-gray-300">
                Operating Margin: <strong class="{{ $summary['total_profit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">{{ $summary['margin_rate'] }}%</strong>
            </span>
        </div>
    </div>

    <!-- Main Report Table -->
    <div class="mt-4">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
