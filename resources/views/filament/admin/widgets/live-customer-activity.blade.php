<div wire:poll.3s="getActiveCustomers">
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden h-full">
        {{-- Header --}}
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="flex items-center justify-center w-7 h-7 rounded-lg bg-violet-500/10">
                        <x-heroicon-o-users class="w-4 h-4 text-violet-500" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Active Customers</h3>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400">Who's sending right now (5 min)</p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-violet-500"></span>
                    </span>
                    <span class="text-[10px] font-medium text-violet-600 dark:text-violet-400">LIVE</span>
                </div>
            </div>
        </div>

        {{-- Customer List --}}
        <div class="divide-y divide-gray-50 dark:divide-gray-800">
            @forelse ($this->getActiveCustomers() as $index => $customer)
                <div class="px-4 py-2.5 hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors">
                    <div class="flex items-center gap-3">
                        {{-- Rank --}}
                        <div class="flex-shrink-0 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold
                            {{ $index === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                                : ($index === 1 ? 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                                : ($index === 2 ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'
                                : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400')) }}">
                            {{ $index + 1 }}
                        </div>

                        {{-- Avatar --}}
                        <div class="flex-shrink-0 w-7 h-7 rounded-full bg-gradient-to-br from-violet-400 to-fuchsia-500 flex items-center justify-center">
                            <span class="text-[10px] font-bold text-white">{{ strtoupper(substr($customer['name'], 0, 1)) }}</span>
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-medium text-gray-900 dark:text-white truncate">{{ $customer['name'] }}</p>
                                <span class="text-[10px] text-gray-400">{{ $customer['last_activity'] }}</span>
                            </div>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-[10px] font-mono text-emerald-600 dark:text-emerald-400">✓{{ $customer['success_count'] }}</span>
                                <span class="text-[10px] font-mono text-red-500 dark:text-red-400">✗{{ $customer['failed_count'] }}</span>
                                <span class="text-[10px] font-mono text-amber-500 dark:text-amber-400">⏳{{ $customer['pending_count'] }}</span>
                                <span class="ml-auto text-[10px] font-semibold text-gray-600 dark:text-gray-300">৳{{ $customer['total_cost'] }}</span>
                            </div>
                        </div>

                        {{-- Message count badge --}}
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center min-w-[28px] h-6 px-1.5 rounded-full text-[11px] font-bold bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400">
                                {{ $customer['message_count'] }}
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <x-heroicon-o-user-group class="w-8 h-8 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                    <p class="text-xs text-gray-400 dark:text-gray-500">No active customers</p>
                    <p class="text-[10px] text-gray-300 dark:text-gray-600">Waiting for activity...</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
