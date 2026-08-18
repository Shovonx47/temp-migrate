<div class="space-y-6">
    {{-- Header Banner & KPIs --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="fi-card flex flex-col gap-y-2 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 rounded-lg bg-emerald-500/10 p-2 text-emerald-500 dark:text-emerald-400">
                    <x-filament::icon icon="heroicon-o-identification" class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Masking Senders</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalMaskingSenders) }}</p>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Brand alphanumeric & shortcode CLIs</p>
        </div>

        <div class="fi-card flex flex-col gap-y-2 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 rounded-lg bg-blue-500/10 p-2 text-blue-500 dark:text-blue-400">
                    <x-filament::icon icon="heroicon-o-signal" class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Configured Operators</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $configuredOperatorsCount }}</p>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">MNO & IPTSP masking routes</p>
        </div>

        <div class="fi-card flex flex-col gap-y-2 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 rounded-lg bg-purple-500/10 p-2 text-purple-500 dark:text-purple-400">
                    <x-filament::icon icon="heroicon-o-server-stack" class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Gateways</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $activeGatewaysCount }}</p>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Gateways with masking rates</p>
        </div>

        <div class="fi-card flex flex-col gap-y-2 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 rounded-lg bg-amber-500/10 p-2 text-amber-500 dark:text-amber-400">
                    <x-filament::icon icon="heroicon-o-currency-dollar" class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg. Masking Rate</p>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">৳{{ number_format($avgMaskingPrice, 4) }}</p>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Average cost per masking part</p>
        </div>
    </div>

    {{-- Tabs & Filters Bar --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        {{-- Tabs --}}
        <div class="inline-flex rounded-lg bg-gray-100 p-1 dark:bg-gray-800">
            <button
                type="button"
                wire:click="$set('activeTab', 'rates')"
                class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-all {{ $activeTab === 'rates' ? 'bg-white text-emerald-600 shadow-sm dark:bg-gray-900 dark:text-emerald-400' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}"
            >
                <x-filament::icon icon="heroicon-o-receipt-percent" class="h-4 w-4" />
                Gateway Operator Masking Rates
            </button>
            <button
                type="button"
                wire:click="$set('activeTab', 'senders')"
                class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-all {{ $activeTab === 'senders' ? 'bg-white text-emerald-600 shadow-sm dark:bg-gray-900 dark:text-emerald-400' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}"
            >
                <x-filament::icon icon="heroicon-o-identification" class="h-4 w-4" />
                Masking Senders (Brand CLIs)
            </button>
        </div>

        {{-- Actions & Filter Inputs --}}
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-[200px]">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search operator, gateway or sender..."
                    class="w-full rounded-lg border-gray-300 bg-white py-2 pl-9 pr-3 text-sm placeholder-gray-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:placeholder-gray-500"
                />
                <x-filament::icon icon="heroicon-o-magnifying-glass" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
            </div>

            <select
                wire:model.live="gatewayFilter"
                class="rounded-lg border-gray-300 bg-white py-2 pl-3 pr-8 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
            >
                <option value="">All Gateways</option>
                @foreach ($gateways as $gw)
                    <option value="{{ $gw->id }}">{{ $gw->name }}</option>
                @endforeach
            </select>

            @if ($activeTab === 'rates')
                <button
                    type="button"
                    wire:click="openNewRateModal"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-700"
                >
                    <x-filament::icon icon="heroicon-o-plus" class="h-4 w-4" />
                    Configure Masking Rate
                </button>
            @endif
        </div>
    </div>

    {{-- TAB 1: GATEWAY OPERATOR MASKING RATES --}}
    @if ($activeTab === 'rates')
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-4">Gateway</th>
                            <th class="px-6 py-4">Operator</th>
                            <th class="px-6 py-4">Country</th>
                            <th class="px-6 py-4">Operator Base Rate</th>
                            <th class="px-6 py-4">Masking Price (Gateway)</th>
                            <th class="px-6 py-4">Operator Spent</th>
                            <th class="px-6 py-4">Gateway Spent</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse ($rates as $rate)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                                        {{ $rate->gateway->name ?? '—' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-400">
                                        {{ $rate->operator->name ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                    {{ $rate->operator->country->name ?? '—' }}
                                </td>
                                <td class="px-6 py-4 font-mono font-medium text-gray-700 dark:text-gray-300">
                                    ৳{{ number_format((float) ($rate->operator->masking_price ?? 0), 4) }}
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    ৳{{ number_format((float) $rate->masking_price, 4) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-700/10 dark:bg-amber-900/30 dark:text-amber-400">
                                        {{ number_format((float) $rate->operator_spent, 2) }} ৳
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10 dark:bg-purple-900/30 dark:text-purple-400">
                                        {{ number_format((float) $rate->gateway_spent, 2) }} ৳
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        type="button"
                                        wire:click="openEditRateModal({{ $rate->id }})"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                                    >
                                        <x-filament::icon icon="heroicon-o-pencil-square" class="h-4 w-4" />
                                        Edit Rate
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <x-filament::icon icon="heroicon-o-receipt-percent" class="h-8 w-8 text-gray-400" />
                                        <p class="font-medium">No masking operator rates found.</p>
                                        <p class="text-xs">Click "Configure Masking Rate" to assign a masking price for an operator under a gateway.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                {{ $rates->links() }}
            </div>
        </div>
    @endif

    {{-- TAB 2: MASKING SENDERS (BRAND CLIs) --}}
    @if ($activeTab === 'senders')
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-4">Masking Sender ID</th>
                            <th class="px-6 py-4">Type</th>
                            <th class="px-6 py-4">Gateway</th>
                            <th class="px-6 py-4">Assigned Users</th>
                            <th class="px-6 py-4">Approval Status</th>
                            <th class="px-6 py-4">Traffic Volume</th>
                            <th class="px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse ($senders as $sender)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-4 font-mono font-bold text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-mono font-semibold text-gray-900 dark:bg-gray-800 dark:text-white">
                                        <x-filament::icon icon="heroicon-o-identification" class="h-3.5 w-3.5 text-emerald-500" />
                                        {{ $sender->sender_id }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-700/10 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        {{ ucfirst($sender->type->value ?? (string) $sender->type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-700 dark:text-gray-300">
                                    {{ $sender->gateway->name ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse ($sender->users->take(2) as $u)
                                            <span class="inline-flex items-center rounded-md bg-gray-100 px-1.5 py-0.5 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                {{ $u->name }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-gray-400">Global / None</span>
                                        @endforelse
                                        @if ($sender->users->count() > 2)
                                            <span class="text-xs text-gray-400">+{{ $sender->users->count() - 2 }} more</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $appStatus = $sender->approval_status?->value ?? (string) $sender->approval_status;
                                        $badgeColor = match ($appStatus) {
                                            'approved' => 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400',
                                            'pending' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-900/30 dark:text-amber-400',
                                            default => 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/30 dark:text-red-400',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $badgeColor }}">
                                        {{ ucfirst($appStatus ?: 'approved') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-mono text-gray-700 dark:text-gray-300">
                                    {{ number_format($sender->messages_count ?? 0) }} msgs
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 text-xs {{ $sender->is_enabled ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}">
                                        <span class="h-2 w-2 rounded-full {{ $sender->is_enabled ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                        {{ $sender->is_enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <x-filament::icon icon="heroicon-o-identification" class="h-8 w-8 text-gray-400" />
                                        <p class="font-medium">No masking senders found.</p>
                                    </div>
                                </td>
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

    {{-- Rate Configuration Modal --}}
    @if ($showRateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-800">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ $editingRateId ? 'Edit Gateway Masking Rate' : 'Configure New Gateway Masking Rate' }}
                    </h3>
                    <button
                        type="button"
                        wire:click="$set('showRateModal', false)"
                        class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800"
                    >
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <form wire:submit="saveRate" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Gateway</label>
                        <select
                            wire:model="selectedGatewayId"
                            class="mt-1 block w-full rounded-lg border-gray-300 bg-white py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            required
                        >
                            @foreach ($gateways as $gw)
                                <option value="{{ $gw->id }}">{{ $gw->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Operator</label>
                        <select
                            wire:model="selectedOperatorId"
                            class="mt-1 block w-full rounded-lg border-gray-300 bg-white py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            required
                        >
                            @foreach ($operators as $op)
                                <option value="{{ $op->id }}">{{ $op->name }} ({{ $op->country->name ?? 'BD' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Masking Price (৳ per part)</label>
                        <input
                            type="number"
                            step="0.0001"
                            wire:model="maskingPrice"
                            placeholder="e.g. 0.4500"
                            class="mt-1 block w-full rounded-lg border-gray-300 bg-white py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            required
                        />
                        <p class="mt-1 text-xs text-gray-500">The gateway price charged per SMS part when sending through this operator route using a Masking Sender.</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-800">
                        <button
                            type="button"
                            wire:click="$set('showRateModal', false)"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700"
                        >
                            Save Rate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
