<x-filament-panels::page>
    @php $stats = $this->getStats(); @endphp

    {{-- ── Stat Cards ─────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">

        {{-- Total KYC --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 text-center">
            <p class="text-3xl font-extrabold text-gray-800 dark:text-white">{{ $stats['total'] }}</p>
            <p class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total KYC</p>
        </div>

        {{-- Pending --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 text-center">
            <p class="text-3xl font-extrabold text-gray-500 dark:text-gray-400">{{ $stats['pending'] }}</p>
            <p class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Pending</p>
        </div>

        {{-- Under Review --}}
        <div class="rounded-xl border border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-950 p-5 text-center">
            <p class="text-3xl font-extrabold text-yellow-600 dark:text-yellow-400">{{ $stats['under_review'] }}</p>
            <p class="mt-1 text-xs font-medium text-yellow-600 dark:text-yellow-400 uppercase tracking-wide">Under Review</p>
        </div>

        {{-- Verified --}}
        <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950 p-5 text-center">
            <p class="text-3xl font-extrabold text-green-600 dark:text-green-400">{{ $stats['verified'] }}</p>
            <p class="mt-1 text-xs font-medium text-green-600 dark:text-green-400 uppercase tracking-wide">Verified</p>
        </div>

        {{-- Rejected --}}
        <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950 p-5 text-center">
            <p class="text-3xl font-extrabold text-red-600 dark:text-red-400">{{ $stats['rejected'] }}</p>
            <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400 uppercase tracking-wide">Rejected</p>
        </div>

        {{-- Resubmit Required --}}
        <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950 p-5 text-center">
            <p class="text-3xl font-extrabold text-blue-600 dark:text-blue-400">{{ $stats['resubmit'] }}</p>
            <p class="mt-1 text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide">Resubmit Required</p>
        </div>

        {{-- Today's Submissions --}}
        <div class="rounded-xl border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950 p-5 text-center">
            <p class="text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">{{ $stats['today_submitted'] }}</p>
            <p class="mt-1 text-xs font-medium text-indigo-600 dark:text-indigo-400 uppercase tracking-wide">Today's Submissions</p>
        </div>

        {{-- Today's Approved --}}
        <div class="rounded-xl border border-teal-200 dark:border-teal-800 bg-teal-50 dark:bg-teal-950 p-5 text-center">
            <p class="text-3xl font-extrabold text-teal-600 dark:text-teal-400">{{ $stats['today_approved'] }}</p>
            <p class="mt-1 text-xs font-medium text-teal-600 dark:text-teal-400 uppercase tracking-wide">Today's Approved</p>
        </div>

    </div>

    {{-- ── Applications Table ──────────────────────────────────────────────────── --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
