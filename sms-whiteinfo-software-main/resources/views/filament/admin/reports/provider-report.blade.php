<x-filament-panels::page>
    {{ $this->table }}

    @php
        $totals = $this->getTotalCalculationData();
    @endphp

    <div class="mt-8">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-gray-100 dark:border-white/5">
                <div>
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                        Provider Summary & Total Calculation
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $this->getTimeframeLabel() }}
                    </p>
                </div>
                <div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300">
                        {{ $this->getFilteredTableQuery()->count() }} Gateways
                    </span>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mt-6">
                <!-- Total SMS Sent -->
                <div class="rounded-xl bg-gray-50/50 p-5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total SMS Messages</div>
                    <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ number_format($totals['total_messages']) }}
                    </div>
                    <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-white/5">
                        <span>Bulk Batches: <strong class="text-gray-700 dark:text-gray-300 font-medium">{{ number_format($totals['batches_count']) }}</strong></span>
                        <span>Dispatched: <strong class="text-gray-700 dark:text-gray-300 font-medium">{{ number_format($totals['sent_count']) }}</strong></span>
                    </div>
                </div>

                <!-- Delivery Performance -->
                <div class="rounded-xl bg-gray-50/50 p-5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Delivered Messages</div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ number_format($totals['delivered_count']) }}</span>
                        <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">({{ $totals['delivery_rate'] }}%)</span>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-white/5">
                        <span>Failed: <strong class="text-gray-700 dark:text-gray-300 font-medium">{{ number_format($totals['failed_count']) }}</strong></span>
                        <span>Delivered: <strong class="text-emerald-600 dark:text-emerald-400 font-medium">{{ number_format($totals['delivered_count']) }}</strong></span>
                    </div>
                </div>

                <!-- Customer Spending (Revenue) -->
                <div class="rounded-xl bg-gray-50/50 p-5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Customer Spend</div>
                    <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        BDT {{ number_format($totals['total_revenue'], 2) }}
                    </div>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-white/5">
                        Revenue charged to customers
                    </div>
                </div>

                <!-- Total Expenditure (Cost + Dipping) -->
                <div class="rounded-xl bg-gray-50/50 p-5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Expenditure</div>
                    <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        BDT {{ number_format($totals['total_expenditure'], 2) }}
                    </div>
                    <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-white/5">
                        <span>Gateway: <strong class="text-gray-700 dark:text-gray-300 font-medium">BDT {{ number_format($totals['total_operator_cost'], 2) }}</strong></span>
                        <span>Dipping: <strong class="text-gray-700 dark:text-gray-300 font-medium">BDT {{ number_format($totals['total_dipping_cost'], 2) }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- Margin Summary Banner -->
            <div class="mt-4 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-gray-50/80 dark:bg-white/5 ring-1 ring-gray-950/5 dark:ring-white/10">
                <div>
                    <div class="text-sm font-medium text-gray-950 dark:text-white">
                        {{ $totals['total_profit'] >= 0 ? 'Net Operating Margin' : 'Net Operating Deficit' }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Customer Spend (BDT {{ number_format($totals['total_revenue'], 2) }}) - Total Expenditure (BDT {{ number_format($totals['total_expenditure'], 2) }})
                    </div>
                </div>
                <div class="text-left sm:text-right">
                    <div class="text-xl font-semibold tracking-tight {{ $totals['total_profit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                        BDT {{ number_format($totals['total_profit'], 2) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Margin: {{ $totals['margin_rate'] }}%
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
