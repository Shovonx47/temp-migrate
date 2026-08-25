<x-filament-panels::page>
    @php
        $stats = $this->getSummaryStats();
        $dateWise = $this->getDateWiseBreakdown();
    @endphp

    {{-- Quantitative KPI Metrics Overview --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 mb-6">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total SMS</div>
            <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total'] ?? 0) }}</div>
            <div class="mt-1 text-xs text-gray-500">Filtered volume</div>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Sent / Delivered</div>
            <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($stats['delivered'] ?? 0) }}</div>
            <div class="mt-1 text-xs text-gray-500">{{ $stats['delivery_rate'] ?? '0%' }} delivered</div>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs font-medium text-rose-600 dark:text-rose-400 uppercase tracking-wider">Failed SMS</div>
            <div class="mt-2 text-2xl font-bold text-rose-600 dark:text-rose-400">{{ number_format($stats['failed'] ?? 0) }}</div>
            <div class="mt-1 text-xs text-rose-500">{{ $stats['failure_rate'] ?? '0%' }} failure rate</div>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider">Success Rate</div>
            <div class="mt-2 text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $stats['success_rate'] ?? '0%' }}</div>
            <div class="mt-1 text-xs text-gray-500">Sent & delivered</div>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs font-medium text-amber-600 dark:text-amber-400 uppercase tracking-wider">Total Cost</div>
            <div class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">৳ {{ number_format($stats['total_cost'] ?? 0, 2) }}</div>
            <div class="mt-1 text-xs text-gray-500">Your campaign spend</div>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs font-medium text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Avg Parts / Segments</div>
            <div class="mt-2 text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $stats['avg_parts'] ?? 1 }}</div>
            <div class="mt-1 text-xs text-gray-500">{{ number_format($stats['total_parts'] ?? 0) }} total parts</div>
        </div>
    </div>

    {{-- Date-Wise Breakdown Section --}}
    @if(count($dateWise) > 0)
    <div class="mb-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h3 class="text-base font-semibold text-gray-950 dark:text-white mb-3">Date-Wise SMS Summary</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-2.5">Date</th>
                        <th class="px-4 py-2.5 text-right">Total SMS</th>
                        <th class="px-4 py-2.5 text-right">Delivered / Sent</th>
                        <th class="px-4 py-2.5 text-right">Failed</th>
                        <th class="px-4 py-2.5 text-right">Success Rate</th>
                        <th class="px-4 py-2.5 text-right">Total Cost (BDT)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($dateWise as $row)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition">
                        <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">{{ $row['date'] }}</td>
                        <td class="px-4 py-2 text-right font-semibold">{{ number_format($row['total']) }}</td>
                        <td class="px-4 py-2 text-right text-emerald-600 dark:text-emerald-400">{{ number_format($row['delivered']) }}</td>
                        <td class="px-4 py-2 text-right text-rose-600 dark:text-rose-400">{{ number_format($row['failed']) }}</td>
                        <td class="px-4 py-2 text-right">
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400">
                                {{ $row['success_rate'] }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right font-mono font-medium">৳ {{ number_format($row['cost'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Detailed Records Table with Interactive Search & Filters --}}
    <div>
        {{ $this->table }}
    </div>
</x-filament-panels::page>
