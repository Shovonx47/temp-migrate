@php
    /** @var array<int, array{label: string, value: string, description: string|null, color: string|null, icon: string|null}> $cards */
    $cards = $cards ?? [];
@endphp

<div wire:init="loadBalances">
    <div class="flex justify-end mb-2">
        <button
            type="button"
            wire:click="refreshBalances"
            wire:loading.attr="disabled"
            class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 disabled:opacity-50"
        >
            <svg wire:loading.remove wire:target="refreshBalances" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
            </svg>
            <svg wire:loading wire:target="refreshBalances" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Refresh
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
        @if ($loading)
            @foreach (range(1, 5) as $i)
                <div class="fi-card fi-wi-stats-overview-stat flex flex-col gap-y-2 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 flex items-center justify-center rounded-lg p-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1 space-y-2">
                            <div class="h-3 w-20 rounded bg-gray-200 dark:bg-gray-700"></div>
                            <div class="h-6 w-16 rounded bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                    </div>
                    <div class="h-3 w-24 rounded bg-gray-200 dark:bg-gray-700"></div>
                </div>
            @endforeach
        @else
            @foreach ($cards as $card)
                @php
                    $colorClasses = match ($card['color']) {
                        'success' => 'text-success-600 dark:text-success-400',
                        'danger' => 'text-danger-600 dark:text-danger-400',
                        'warning' => 'text-warning-600 dark:text-warning-400',
                        'primary' => 'text-primary-600 dark:text-primary-400',
                        default => 'text-gray-900 dark:text-white',
                    };

                    $iconColorClasses = match ($card['color']) {
                        'success' => 'text-success-500 dark:text-success-400 bg-success-500/10',
                        'danger' => 'text-danger-500 dark:text-danger-400 bg-danger-500/10',
                        'warning' => 'text-warning-500 dark:text-warning-400 bg-warning-500/10',
                        'primary' => 'text-primary-500 dark:text-primary-400 bg-primary-500/10',
                        default => 'text-gray-500 dark:text-gray-400 bg-gray-500/10',
                    };
                @endphp

                <div class="fi-card fi-wi-stats-overview-stat flex flex-col gap-y-2 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center gap-3">
                        @if ($card['icon'])
                            <div class="flex-shrink-0 {{ $iconColorClasses }} flex items-center justify-center rounded-lg p-2">
                                <x-dynamic-component :component="$card['icon']" class="h-5 w-5" />
                            </div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ $card['label'] }}
                            </p>

                            <p class="mt-1 text-2xl font-semibold truncate {{ $colorClasses }}">
                                {{ is_numeric($card['value']) ? number_format((float) $card['value'], 4, '.', '') : $card['value'] }}
                            </p>
                        </div>
                    </div>

                    @if ($card['description'])
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $card['description'] }}
                        </p>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</div>
