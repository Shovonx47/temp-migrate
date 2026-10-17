@php
    $base = clone $livewire->getFilteredTableQuery();

    $totalAdd = (clone $base)->where('amount', '>', 0)->sum('amount');
    $totalMinus = abs((clone $base)->where('amount', '<', 0)->sum('amount'));
    $net = $totalAdd - $totalMinus;
    $transactions = (clone $base)->count();

    $adminAdd = (clone $base)
        ->whereNotNull('deposited_by_id')
        ->where('amount', '>', 0)
        ->sum('amount');

    $adminMinus = abs((clone $base)
        ->whereNotNull('deposited_by_id')
        ->where('amount', '<', 0)
        ->sum('amount'));

    $pipraAdd = (clone $base)
        ->whereNull('deposited_by_id')
        ->where('payment_method', 'piprapay')
        ->where('amount', '>', 0)
        ->sum('amount');

    $pipraMinus = abs((clone $base)
        ->whereNull('deposited_by_id')
        ->where('payment_method', 'piprapay')
        ->where('amount', '<', 0)
        ->sum('amount'));

    $daily = (clone $base)
        ->selectRaw('DATE(created_at) as day')
        ->selectRaw('SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as add_amount')
        ->selectRaw('SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as minus_amount')
        ->selectRaw('COUNT(*) as transactions')
        ->groupByRaw('DATE(created_at)')
        ->orderByDesc('day')
        ->get();

    $activePreset = $livewire->tableFilters['date_preset']['value'] ?? null;

    $money = fn ($value) => '৳' . number_format((float) $value, 2);
@endphp

<div class="mb-4 space-y-3">

    {{-- REPORT DATE CONTROL --}}
    <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-white/10 dark:bg-white/5">
        <div class="mb-2 flex items-center justify-between">
            <div>
                <div class="text-sm font-bold text-gray-900 dark:text-white">
                    Finance Report
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Select reporting period
                </div>
            </div>

            @if ($activePreset)
                <button
                    type="button"
                    wire:click="clearDatePreset"
                    class="text-xs font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                >
                    Clear
                </button>
            @endif
        </div>

        <div class="space-y-3">

            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">

                @foreach ([
                    'today' => 'Today',
                    'yesterday' => 'Yesterday',
                    'last_7_days' => 'Last 7 Days',
                    'this_week' => 'This Week',
                    'this_month' => 'This Month',
                ] as $value => $label)

                    <button
                        type="button"
                        wire:click="setDatePreset('{{ $value }}')"
                        class="rounded-lg border px-3 py-2 text-xs font-semibold transition
                        {{ $activePreset === $value
                            ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400'
                            : 'border-gray-200 bg-gray-50 text-gray-700 hover:border-primary-400 hover:bg-white dark:border-white/10 dark:bg-white/5 dark:text-gray-300' }}"
                    >
                        {{ $label }}
                    </button>

                @endforeach

                <details class="relative">
                    <summary class="list-none cursor-pointer rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-center text-xs font-semibold text-gray-700 hover:border-primary-400 hover:bg-white dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
                        Custom Date
                    </summary>

                    <div class="absolute left-0 z-50 mt-2 w-[min(92vw,520px)] rounded-xl border border-gray-200 bg-white p-4 shadow-xl dark:border-white/10 dark:bg-gray-900">

                        <div class="mb-3">
                            <div class="text-sm font-bold text-gray-900 dark:text-white">
                                Custom Date Range
                            </div>

                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                Select From Date and To Date
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">

                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    From Date
                                </label>

                                <input
                                    type="date"
                                    wire:model="customFromDate"
                                    class="block w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                >
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    To Date
                                </label>

                                <input
                                    type="date"
                                    wire:model="customUntilDate"
                                    class="block w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                >
                            </div>

                        </div>

                        <div class="mt-3 border-t border-gray-200 pt-3 dark:border-white/10">
                            <button
    type="button"
    wire:click="applyCustomDate"
    style="display:block;width:100%;padding:10px 16px;background:#16a34a!important;color:#ffffff!important;border:0;border-radius:8px;font-size:14px;font-weight:700;line-height:20px;text-align:center;cursor:pointer;"
>
    Apply Date Filter
</button>
                        </div>

                    </div>
                </details>

            </div>

        </div>

    {{-- SUMMARY --}}
    <div class="grid grid-cols-2 gap-2 lg:grid-cols-4">

        <div class="rounded-xl border border-gray-200 bg-white px-3 py-2 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="text-xs text-gray-500 dark:text-gray-400">
                Total Add / Credit
            </div>
            <div class="mt-1 text-lg font-bold text-success-600">
                {{ $money($totalAdd) }}
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white px-3 py-2 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="text-xs text-gray-500 dark:text-gray-400">
                Total Minus / Debit
            </div>
            <div class="mt-1 text-lg font-bold text-danger-600">
                {{ $money($totalMinus) }}
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white px-3 py-2 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="text-xs text-gray-500 dark:text-gray-400">
                Net Amount
            </div>
            <div class="mt-1 text-lg font-bold {{ $net >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                {{ $money($net) }}
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white px-3 py-2 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="text-xs text-gray-500 dark:text-gray-400">
                Transactions
            </div>
            <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                {{ number_format($transactions) }}
            </div>
        </div>

    </div>

    {{-- ADMIN / PIPRAPAY --}}
    <div class="grid grid-cols-1 gap-2 md:grid-cols-2">

        <div class="rounded-xl border border-gray-200 bg-white px-3 py-2 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="mb-1 text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Admin
            </div>

            <div class="flex justify-between">
                <span class="font-bold text-success-600">
                    +{{ $money($adminAdd) }}
                </span>

                <span class="font-bold text-danger-600">
                    -{{ $money($adminMinus) }}
                </span>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white px-3 py-2 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="mb-1 text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                PipraPay
            </div>

            <div class="flex justify-between">
                <span class="font-bold text-success-600">
                    +{{ $money($pipraAdd) }}
                </span>

                <span class="font-bold text-danger-600">
                    -{{ $money($pipraMinus) }}
                </span>
            </div>
        </div>

    </div>

    {{-- DAILY ACCOUNTING --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-white/5">

        <div class="border-b border-gray-200 px-3 py-2 dark:border-white/10">
            <div class="text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                Daily Accounting
            </div>
        </div>

        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-xs text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                        <th class="px-3 py-2 text-left">Date</th>
                        <th class="px-3 py-2 text-right">Add</th>
                        <th class="px-3 py-2 text-right">Minus</th>
                        <th class="px-3 py-2 text-right">Net</th>
                        <th class="px-3 py-2 text-right">Transactions</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($daily as $row)

                        @php
                            $add = (float) $row->add_amount;
                            $minus = (float) $row->minus_amount;
                            $dailyNet = $add - $minus;
                        @endphp

                        <tr class="border-b border-gray-100 last:border-0 dark:border-white/5">

                            <td class="px-3 py-2 font-medium">
                                {{ Carbon\Carbon::parse($row->day)->format('d M Y') }}
                            </td>

                            <td class="px-3 py-2 text-right font-semibold text-success-600">
                                +{{ $money($add) }}
                            </td>

                            <td class="px-3 py-2 text-right font-semibold text-danger-600">
                                -{{ $money($minus) }}
                            </td>

                            <td class="px-3 py-2 text-right font-semibold {{ $dailyNet >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                {{ $money($dailyNet) }}
                            </td>

                            <td class="px-3 py-2 text-right font-medium">
                                {{ number_format($row->transactions) }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="px-3 py-5 text-center text-sm text-gray-500">
                                No data for selected period.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>
    </div>

</div>
