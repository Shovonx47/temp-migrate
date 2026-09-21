@php
    /** @var \App\Services\Sms\Data\StatCard[] $cards */
    $cards = $cards ?? [];
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
    @foreach ($cards as $card)
        @php
            $colorClasses = match ($card->color) {
                'success' => 'text-success-600 dark:text-success-400',
                'danger' => 'text-danger-600 dark:text-danger-400',
                'warning' => 'text-warning-600 dark:text-warning-400',
                'primary' => 'text-primary-600 dark:text-primary-400',
                default => 'text-gray-900 dark:text-white',
            };

            $iconColorClasses = match ($card->color) {
                'success' => 'text-success-500 dark:text-success-400 bg-success-500/10',
                'danger' => 'text-danger-500 dark:text-danger-400 bg-danger-500/10',
                'warning' => 'text-warning-500 dark:text-warning-400 bg-warning-500/10',
                'primary' => 'text-primary-500 dark:text-primary-400 bg-primary-500/10',
                default => 'text-gray-500 dark:text-gray-400 bg-gray-500/10',
            };
        @endphp

        <div class="fi-card fi-wi-stats-overview-stat flex flex-col gap-y-2 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center gap-3">
                @if ($card->icon)
                    <div class="flex-shrink-0 {{ $iconColorClasses }} flex items-center justify-center rounded-lg p-2">
                        <x-dynamic-component :component="$card->icon" class="h-5 w-5" />
                    </div>
                @endif

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ $card->label }}
                    </p>

                    <p class="mt-1 text-2xl font-semibold {{ $colorClasses }}">
                        {{ $card->value }}
                    </p>
                </div>
            </div>

            @if ($card->description)
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $card->description }}
                </p>
            @endif
        </div>
    @endforeach
</div>
