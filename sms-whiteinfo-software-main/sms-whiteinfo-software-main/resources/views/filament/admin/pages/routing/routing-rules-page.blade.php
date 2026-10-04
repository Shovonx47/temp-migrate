<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-6 shadow-xs">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Dynamic Route Plans & Priority Failover</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Configure traffic-specific route plans (OTP, Transactional, Promotional) and define provider priority chains (Priority 1 ➔ Priority 2 ➔ Priority 3).
                When a primary provider encounters a network timeout, carrier refusal, or error, the system automatically routes the message to the next priority provider in the chain.
            </p>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
