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
                        Customer Summary & Total Calculation
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Aggregate financial and verification metrics for the selected customer filters
                    </p>
                </div>
                <div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300">
                        {{ number_format($totals['total_customers']) }} {{ Str::plural('Customer', $totals['total_customers']) }}
                    </span>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mt-6">
                <!-- Total Customer Balance -->
                <div class="rounded-xl bg-gray-50/50 p-5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Customer Balance</div>
                    <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        BDT {{ number_format($totals['total_balance'], 2) }}
                    </div>
                    <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-white/5">
                        <span>Average: <strong class="text-gray-700 dark:text-gray-300 font-medium">BDT {{ number_format($totals['avg_balance'], 2) }}</strong></span>
                        <span>Funded: <strong class="text-emerald-600 dark:text-emerald-400 font-medium">{{ number_format($totals['funded_customers_count']) }}</strong></span>
                    </div>
                </div>

                <!-- Total Customers Count -->
                <div class="rounded-xl bg-gray-50/50 p-5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Customers</div>
                    <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ number_format($totals['total_customers']) }}
                    </div>
                    <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-white/5">
                        <span>Zero Balance: <strong class="text-gray-700 dark:text-gray-300 font-medium">{{ number_format($totals['zero_balance_count']) }}</strong></span>
                        <span>With NID: <strong class="text-gray-700 dark:text-gray-300 font-medium">{{ number_format($totals['with_nid_count']) }}</strong></span>
                    </div>
                </div>

                <!-- KYC Verification Status -->
                <div class="rounded-xl bg-gray-50/50 p-5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">KYC Verification</div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ number_format($totals['verified_kyc_count']) }}</span>
                        <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">({{ $totals['kyc_verified_rate'] }}%)</span>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-white/5">
                        <span>Under Review: <strong class="text-amber-600 dark:text-amber-400 font-medium">{{ number_format($totals['under_review_kyc_count']) }}</strong></span>
                        <span>Pending/Other: <strong class="text-gray-700 dark:text-gray-300 font-medium">{{ number_format($totals['other_kyc_count']) }}</strong></span>
                    </div>
                </div>

                <!-- Account Status -->
                <div class="rounded-xl bg-gray-50/50 p-5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Account Standing</div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ number_format($totals['approved_status_count']) }}</span>
                        <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">({{ $totals['approved_rate'] }}%)</span>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-white/5">
                        <span>Pending: <strong class="text-amber-600 dark:text-amber-400 font-medium">{{ number_format($totals['pending_status_count']) }}</strong></span>
                        <span>Inactive/Banned: <strong class="text-rose-600 dark:text-rose-400 font-medium">{{ number_format($totals['inactive_status_count']) }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- Financial Summary Banner -->
            <div class="mt-4 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-gray-50/80 dark:bg-white/5 ring-1 ring-gray-950/5 dark:ring-white/10">
                <div>
                    <div class="text-sm font-medium text-gray-950 dark:text-white">
                        Aggregate Customer Deposits & Wallet Reserve
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Total funds currently available in active customer wallets for SMS consumption
                    </div>
                </div>
                <div class="text-left sm:text-right">
                    <div class="text-xl font-semibold tracking-tight text-emerald-600 dark:text-emerald-400">
                        BDT {{ number_format($totals['total_balance'], 2) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $totals['funded_percentage'] }}% of customers currently maintain a positive balance
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
