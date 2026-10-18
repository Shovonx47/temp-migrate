<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-6 shadow-xs">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Provider Status & Real-time Connectivity</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Monitor HTTP latency, network reachability, and credit balances across all configured SMS gateways.
                Providers experiencing high latency or downtime will trigger automatic failovers when priority routing is enabled.
            </p>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
