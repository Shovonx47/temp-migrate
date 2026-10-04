<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-6 shadow-xs">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Automatic Failover Audit & Exception Log</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Shows all historical and real-time occurrences where an outbound SMS encountered an error or timeout on a primary provider and was automatically rescued by routing to the next priority provider.
            </p>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
