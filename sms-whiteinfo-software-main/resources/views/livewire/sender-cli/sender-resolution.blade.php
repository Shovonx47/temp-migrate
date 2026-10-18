<div class="space-y-6">
    {{-- Header Banner --}}
    <div class="rounded-2xl bg-gradient-to-r from-blue-700 via-indigo-700 to-purple-700 p-6 text-white shadow-lg">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-white/20 px-3 py-1 text-xs font-semibold text-white backdrop-blur-md">
                    <x-filament::icon icon="heroicon-o-arrows-right-left" class="h-4 w-4" />
                    Intelligent CLI Dispatch Engine
                </div>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-white">Sender & Route Resolution Engine</h1>
                <p class="mt-1 text-sm text-blue-100 max-w-2xl">
                    Real-time carrier route diagnosis, gateway channel resolution, operator prefix matching, and dynamic tariff calculation for outgoing SMS traffic.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white backdrop-blur-md ring-1 ring-white/20">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Resolution Engine Online
                </span>
            </div>
        </div>
    </div>

    {{-- Interactive Live Resolution Simulator --}}
    <div class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex items-center gap-3 border-b border-gray-200 pb-4 dark:border-gray-800">
            <div class="rounded-lg bg-blue-500/10 p-2 text-blue-600 dark:text-blue-400">
                <x-filament::icon icon="heroicon-o-magnifying-glass" class="h-5 w-5" />
            </div>
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Live Sender & Prefix Resolution Diagnostic</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Enter a sender ID and recipient phone to view the step-by-step resolution chain and calculated routing cost.</p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Simulator Inputs --}}
            <div class="space-y-4 rounded-xl bg-gray-50 p-5 dark:bg-gray-800/50">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sender ID / CLI Input</label>
                    <input
                        type="text"
                        wire:model.live.debounce.200ms="simSenderInput"
                        placeholder="e.g. WHITEINFO or 01712345678"
                        class="mt-1 block w-full rounded-lg border-gray-300 bg-white py-2 font-mono text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Destination MSISDN</label>
                    <input
                        type="text"
                        wire:model.live.debounce.200ms="simRecipientPhone"
                        placeholder="e.g. 01712345678 or +8801812345678"
                        class="mt-1 block w-full rounded-lg border-gray-300 bg-white py-2 font-mono text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>

                <div class="pt-2 text-xs text-gray-500">
                    <p class="font-medium text-gray-700 dark:text-gray-300">Quick Test Senders:</p>
                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        @foreach ($senders->take(4) as $s)
                            <button
                                type="button"
                                wire:click="selectSender('{{ $s->sender_id }}')"
                                class="rounded bg-white px-2 py-1 text-xs font-mono font-medium text-gray-700 shadow-sm hover:bg-blue-50 hover:text-blue-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                            >
                                {{ $s->sender_id }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Pipeline Visualization Output --}}
            <div class="lg:col-span-2 space-y-3">
                @if ($resolutionOutput)
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        {{-- Step 1: Sender Classification --}}
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <div class="flex items-center gap-2 text-xs font-semibold text-gray-500">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-[10px] font-bold text-blue-600 dark:bg-blue-900/50 dark:text-blue-300">1</span>
                                SENDER CLASSIFICATION
                            </div>
                            <div class="mt-2">
                                <p class="font-mono text-base font-bold text-gray-900 dark:text-white">{{ $resolutionOutput['senderId'] }}</p>
                                <div class="mt-1 flex items-center gap-2">
                                    <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium {{ $resolutionOutput['isMasking'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300' }}">
                                        {{ $resolutionOutput['senderType'] }}
                                    </span>
                                    <span class="text-xs text-gray-400">
                                        {{ $resolutionOutput['isRegistered'] ? '✓ Registered' : '⚡ Dynamic' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Step 2: Operator & Prefix Resolution --}}
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <div class="flex items-center gap-2 text-xs font-semibold text-gray-500">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-300">2</span>
                                OPERATOR IDENTIFICATION
                            </div>
                            <div class="mt-2">
                                <p class="text-base font-bold text-gray-900 dark:text-white">{{ $resolutionOutput['operatorName'] }}</p>
                                <p class="mt-1 text-xs text-gray-500 font-mono">
                                    Target: <span class="text-gray-900 dark:text-white">{{ $resolutionOutput['recipientPhone'] }}</span> (Bangladesh Network)
                                </p>
                            </div>
                        </div>

                        {{-- Step 3: Gateway Channel --}}
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <div class="flex items-center gap-2 text-xs font-semibold text-gray-500">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-purple-100 text-[10px] font-bold text-purple-600 dark:bg-purple-900/50 dark:text-purple-300">3</span>
                                GATEWAY DISPATCH CHANNEL
                            </div>
                            <div class="mt-2">
                                <p class="text-base font-bold text-gray-900 dark:text-white">{{ $resolutionOutput['gatewayName'] }}</p>
                                <p class="mt-1 text-xs text-purple-600 dark:text-purple-400 font-mono">
                                    Driver: {{ strtoupper($resolutionOutput['gatewayDriver']) }} • Rate Limited
                                </p>
                            </div>
                        </div>

                        {{-- Step 4: Rate & Cost Matrix --}}
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <div class="flex items-center gap-2 text-xs font-semibold text-gray-500">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-amber-100 text-[10px] font-bold text-amber-600 dark:bg-amber-900/50 dark:text-amber-300">4</span>
                                RESOLVED TARIFF & COST
                            </div>
                            <div class="mt-2 flex items-baseline gap-3">
                                <div>
                                    <span class="text-xs text-gray-500">Gateway Cost:</span>
                                    <p class="font-mono text-base font-bold text-emerald-600 dark:text-emerald-400">
                                        ৳{{ number_format((float) $resolutionOutput['gatewayChargedRate'], 4) }}
                                    </p>
                                </div>
                                <div class="border-l border-gray-200 pl-3 dark:border-gray-700">
                                    <span class="text-xs text-gray-500">Operator Base:</span>
                                    <p class="font-mono text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        ৳{{ number_format((float) $resolutionOutput['operatorBaseCost'], 4) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Step 5: Route Verdict Banner --}}
                    <div class="flex items-center justify-between rounded-xl bg-emerald-50 p-4 text-xs text-emerald-800 ring-1 ring-emerald-600/20 dark:bg-emerald-950/30 dark:text-emerald-300">
                        <div class="flex items-center gap-2 font-medium">
                            <x-filament::icon icon="heroicon-o-check-badge" class="h-5 w-5 text-emerald-600" />
                            <span>Resolution Path Verified: <strong>{{ $resolutionOutput['senderId'] }}</strong> &rarr; <strong>{{ $resolutionOutput['operatorName'] }}</strong> via <strong>{{ $resolutionOutput['gatewayName'] }}</strong></span>
                        </div>
                        <span class="font-mono font-bold">{{ $resolutionOutput['routeHealth'] }}</span>
                    </div>
                @else
                    <div class="flex h-full items-center justify-center rounded-xl border border-dashed border-gray-300 p-8 text-center text-gray-400 dark:border-gray-700">
                        <p class="text-sm">Enter a Sender ID and MSISDN to resolve the dispatch route.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sender Resolution Matrix Table --}}
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex items-center justify-between border-b border-gray-200 p-6 dark:border-gray-800">
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Active Sender Resolution Matrix</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">All registered senders and their bound gateway dispatch channels.</p>
            </div>
            <div class="w-64">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Filter senders..."
                    class="w-full rounded-lg border-gray-300 bg-white py-1.5 pl-3 pr-3 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <tr>
                        <th class="px-6 py-4">Sender ID</th>
                        <th class="px-6 py-4">Type</th>
                        <th class="px-6 py-4">Assigned Gateway Channel</th>
                        <th class="px-6 py-4">Assigned Users</th>
                        <th class="px-6 py-4">Historical Traffic</th>
                        <th class="px-6 py-4">Approval State</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($senders as $s)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                            <td class="px-6 py-4 font-mono font-bold text-gray-900 dark:text-white">
                                {{ $s->sender_id }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium {{ ($s->type?->isMasking() ?? true) ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300' }}">
                                    {{ $s->type?->getLabel() ?? 'Masking' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    {{ $s->gateway->name ?? 'Default Gateway' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                {{ $s->users->count() > 0 ? $s->users->pluck('name')->join(', ') : 'Global / All Users' }}
                            </td>
                            <td class="px-6 py-4 font-mono text-gray-700 dark:text-gray-300">
                                {{ number_format($s->messages_count ?? 0) }} msgs
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-400">
                                    Approved
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button
                                    type="button"
                                    wire:click="selectSender('{{ $s->sender_id }}')"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400"
                                >
                                    Test Route &rarr;
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-400">No senders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-800">
            {{ $senders->links() }}
        </div>
    </div>
</div>
