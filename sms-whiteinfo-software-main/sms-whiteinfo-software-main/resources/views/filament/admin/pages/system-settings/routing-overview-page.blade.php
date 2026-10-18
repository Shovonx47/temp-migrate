<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs">
                <span class="text-sm font-medium text-gray-500">Configured Route Plans</span>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalPlans }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs">
                <span class="text-sm font-medium text-gray-500">Active Plans</span>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $activePlans }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs">
                <span class="text-sm font-medium text-gray-500">Routing Rules</span>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $totalRules }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs">
                <span class="text-sm font-medium text-gray-500">Active Rules</span>
                <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $activeRules }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <a href="{{ route('filament.admin.pages.routing.rules') }}" class="block p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:border-primary-500 transition shadow-xs">
                <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-adjustments-horizontal" class="w-5 h-5 text-primary-500" />
                    Configure Route Plans & Rule Matrix
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Manage traffic priority chains (Priority 1 ➔ Priority 2 ➔ Priority 3) for OTP, Transactional, and Promotional SMS, along with operator/country/sender rule overrides.</p>
            </a>

            <a href="{{ route('filament.admin.pages.routing.failover') }}" class="block p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:border-primary-500 transition shadow-xs">
                <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-arrow-path" class="w-5 h-5 text-amber-500" />
                    Failover Audit & Exception History
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Inspect real-time logs of messages that experienced provider timeouts or carrier refusals and were automatically rescued by failover routing.</p>
            </a>
        </div>
    </div>
</x-filament-panels::page>
