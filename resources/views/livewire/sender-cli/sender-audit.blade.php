<div class="space-y-6">
    {{-- Header KPIs --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="fi-card flex flex-col gap-y-2 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 rounded-lg bg-blue-500/10 p-2 text-blue-500 dark:text-blue-400">
                    <x-filament::icon icon="heroicon-o-identification" class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Senders</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalSenders) }}</p>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Masking & Non-masking active senders</p>
        </div>

        <div class="fi-card flex flex-col gap-y-2 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 rounded-lg bg-purple-500/10 p-2 text-purple-500 dark:text-purple-400">
                    <x-filament::icon icon="heroicon-o-paper-airplane" class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Dispatches</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalMessages) }}</p>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Cumulative SMS sent by registered senders</p>
        </div>

        <div class="fi-card flex flex-col gap-y-2 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 rounded-lg bg-emerald-500/10 p-2 text-emerald-500 dark:text-emerald-400">
                    <x-filament::icon icon="heroicon-o-check-badge" class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Delivery Success Rate</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $overallSuccessRate }}%</p>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($deliveredMessages) }} delivered successfully</p>
        </div>

        <div class="fi-card flex flex-col gap-y-2 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 rounded-lg bg-amber-500/10 p-2 text-amber-500 dark:text-amber-400">
                    <x-filament::icon icon="heroicon-o-banknotes" class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Revenue Generated</p>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">৳{{ number_format($totalRevenue, 2) }}</p>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Sender generated billing volume</p>
        </div>
    </div>

    {{-- Controls & View Switcher --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="inline-flex rounded-lg bg-gray-100 p-1 dark:bg-gray-800">
            <button
                type="button"
                wire:click="$set('activeView', 'table')"
                class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-all {{ $activeView === 'table' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-900 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}"
            >
                <x-filament::icon icon="heroicon-o-table-cells" class="h-4 w-4" />
                Sender Audit Ledger
            </button>
            <button
                type="button"
                wire:click="$set('activeView', 'activity')"
                class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-all {{ $activeView === 'activity' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-900 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}"
            >
                <x-filament::icon icon="heroicon-o-clock" class="h-4 w-4" />
                Live Dispatch Stream (Recent 50)
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-[220px]">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search sender, user or gateway..."
                    class="w-full rounded-lg border-gray-300 bg-white py-2 pl-9 pr-3 text-sm placeholder-gray-400 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                />
                <x-filament::icon icon="heroicon-o-magnifying-glass" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
            </div>

            <select
                wire:model.live="typeFilter"
                class="rounded-lg border-gray-300 bg-white py-2 pl-3 pr-8 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
            >
                <option value="">All Categories</option>
                <option value="masking">Masking Only</option>
                <option value="non_masking">Non-Masking (Numeric)</option>
            </select>

            <select
                wire:model.live="gatewayFilter"
                class="rounded-lg border-gray-300 bg-white py-2 pl-3 pr-8 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
            >
                <option value="">All Gateways</option>
                @foreach ($gateways as $gw)
                    <option value="{{ $gw->id }}">{{ $gw->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- VIEW 1: SENDER AUDIT LEDGER TABLE --}}
    @if ($activeView === 'table')
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-4">Sender / CLI</th>
                            <th class="px-6 py-4">Category</th>
                            <th class="px-6 py-4">Gateway</th>
                            <th class="px-6 py-4">Assigned Customers</th>
                            <th class="px-6 py-4">Total SMS Sent</th>
                            <th class="px-6 py-4">Delivered</th>
                            <th class="px-6 py-4">Failed</th>
                            <th class="px-6 py-4">Success Rate</th>
                            <th class="px-6 py-4">Total Volume</th>
                            <th class="px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse ($senders as $s)
                            @php
                                $total = $s->total_messages ?? 0;
                                $delivered = $s->delivered_messages ?? 0;
                                $failed = $s->failed_messages ?? 0;
                                $rate = $total > 0 ? round(($delivered / $total) * 100, 1) : 100;
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-4 font-mono font-bold text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-mono font-semibold text-gray-900 dark:bg-gray-800 dark:text-white">
                                        <x-filament::icon icon="heroicon-o-identification" class="h-3.5 w-3.5 text-blue-500" />
                                        {{ $s->sender_id }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium {{ ($s->type?->isMasking() ?? true) ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300' }}">
                                        {{ $s->type?->getLabel() ?? 'Masking' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-700 dark:text-gray-300">
                                    {{ $s->gateway->name ?? 'Default Gateway' }}
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse ($s->users->take(2) as $u)
                                            <span class="inline-flex rounded bg-gray-100 px-1.5 py-0.5 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                {{ $u->name }}
                                            </span>
                                        @empty
                                            <span class="text-gray-400">Global</span>
                                        @endforelse
                                        @if ($s->users->count() > 2)
                                            <span class="text-gray-400">+{{ $s->users->count() - 2 }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-mono font-semibold text-gray-900 dark:text-white">
                                    {{ number_format($total) }}
                                </td>
                                <td class="px-6 py-4 font-mono text-emerald-600 dark:text-emerald-400">
                                    {{ number_format($delivered) }}
                                </td>
                                <td class="px-6 py-4 font-mono text-red-600 dark:text-red-400">
                                    {{ number_format($failed) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-16 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $rate }}%"></div>
                                        </div>
                                        <span class="font-mono text-xs font-semibold {{ $rate >= 90 ? 'text-emerald-600 dark:text-emerald-400' : ($rate >= 70 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                                            {{ $rate }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-mono font-medium text-gray-900 dark:text-white">
                                    ৳{{ number_format((float) ($s->total_spend ?? 0), 2) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 text-xs {{ $s->is_enabled ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}">
                                        <span class="h-2 w-2 rounded-full {{ $s->is_enabled ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                        {{ $s->is_enabled ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-8 text-center text-gray-400">No senders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                {{ $senders->links() }}
            </div>
        </div>
    @endif

    {{-- VIEW 2: LIVE DISPATCH STREAM --}}
    @if ($activeView === 'activity')
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Recent 50 SMS Dispatches by Senders</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-3">Timestamp</th>
                            <th class="px-6 py-3">Sender ID</th>
                            <th class="px-6 py-3">Recipient</th>
                            <th class="px-6 py-3">User / Customer</th>
                            <th class="px-6 py-3">Gateway</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 font-mono text-xs dark:divide-gray-800">
                        @forelse ($recentDispatches as $msg)
                            @php
                                $statusBadge = match ($msg->status?->value ?? (string) $msg->status) {
                                    'delivered' => 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-400',
                                    'sent' => 'text-blue-600 bg-blue-50 dark:bg-blue-950/40 dark:text-blue-400',
                                    'failed' => 'text-red-600 bg-red-50 dark:bg-red-950/40 dark:text-red-400',
                                    default => 'text-amber-600 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-400',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-3 text-gray-500">{{ $msg->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td class="px-6 py-3 font-bold text-gray-900 dark:text-white">{{ $msg->sender->sender_id ?? '—' }}</td>
                                <td class="px-6 py-3 text-gray-700 dark:text-gray-300">{{ $msg->to }}</td>
                                <td class="px-6 py-3 font-sans">{{ $msg->user->name ?? '—' }}</td>
                                <td class="px-6 py-3 font-sans">{{ $msg->gateway->name ?? '—' }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex rounded px-2 py-0.5 font-sans font-semibold {{ $statusBadge }}">
                                        {{ strtoupper($msg->status?->value ?? (string) $msg->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 font-bold text-gray-900 dark:text-white">৳{{ number_format((float) $msg->cost, 4) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center font-sans text-gray-400">No recent dispatches logged.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
