<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs">
                <span class="text-sm font-medium text-gray-500">Total Gateways</span>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalGateways }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs">
                <span class="text-sm font-medium text-gray-500">Active Gateways</span>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $activeGateways }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs">
                <span class="text-sm font-medium text-gray-500">Healthy & Online</span>
                <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $healthyGateways }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs">
                <span class="text-sm font-medium text-gray-500">OTP Routes</span>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $otpGateways }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="{{ route('filament.admin.resources.gateways.index') }}" class="block p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:border-primary-500 transition shadow-xs">
                <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-server" class="w-5 h-5 text-primary-500" />
                    Manage Gateways & Credentials
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Add, edit, test credentials (Infozillion, ReveSMS, QSMS, Anbernet), configure timeouts and dipping.</p>
            </a>

            <a href="{{ route('filament.admin.pages.providers.health') }}" class="block p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:border-primary-500 transition shadow-xs">
                <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-heart" class="w-5 h-5 text-emerald-500" />
                    Provider Health & Latency
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Live HTTP ping latency metrics, provider balance inquiries, and automated reachability monitor.</p>
            </a>

            <a href="{{ route('filament.admin.pages.routing.rules') }}" class="block p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:border-primary-500 transition shadow-xs">
                <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-arrows-right-left" class="w-5 h-5 text-amber-500" />
                    Route Plans & Failover
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Build priority 1 ➔ 2 ➔ 3 failover chains and traffic-type rules (OTP, Transactional, Promotional).</p>
            </a>
        </div>
    </div>
</x-filament-panels::page>
