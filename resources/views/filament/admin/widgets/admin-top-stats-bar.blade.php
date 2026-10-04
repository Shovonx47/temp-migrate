<x-filament-widgets::widget>
    @php
        $stats = $this->getStats();

        $colorConfigs = [
            'purple' => [
                'card' => 'border-purple-200/90 hover:border-purple-400 dark:border-purple-900/60 dark:hover:border-purple-500 bg-gradient-to-br from-white via-white to-purple-50/40 dark:from-gray-900 dark:via-gray-900 dark:to-purple-950/30 hover:shadow-purple-500/10',
                'icon_bg' => 'bg-purple-100 text-purple-600 dark:bg-purple-900/60 dark:text-purple-300',
                'accent' => 'text-purple-600 dark:text-purple-400',
                'dot' => 'bg-purple-500',
            ],
            'green' => [
                'card' => 'border-emerald-200/90 hover:border-emerald-400 dark:border-emerald-900/60 dark:hover:border-emerald-500 bg-gradient-to-br from-white via-white to-emerald-50/40 dark:from-gray-900 dark:via-gray-900 dark:to-emerald-950/30 hover:shadow-emerald-500/10',
                'icon_bg' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/60 dark:text-emerald-300',
                'accent' => 'text-emerald-600 dark:text-emerald-400',
                'dot' => 'bg-emerald-500',
            ],
            'blue' => [
                'card' => 'border-sky-200/90 hover:border-sky-400 dark:border-sky-900/60 dark:hover:border-sky-500 bg-gradient-to-br from-white via-white to-sky-50/40 dark:from-gray-900 dark:via-gray-900 dark:to-sky-950/30 hover:shadow-sky-500/10',
                'icon_bg' => 'bg-sky-100 text-sky-600 dark:bg-sky-900/60 dark:text-sky-300',
                'accent' => 'text-sky-600 dark:text-sky-400',
                'dot' => 'bg-sky-500',
            ],
            'orange' => [
                'card' => 'border-amber-200/90 hover:border-amber-400 dark:border-amber-900/60 dark:hover:border-amber-500 bg-gradient-to-br from-white via-white to-amber-50/40 dark:from-gray-900 dark:via-gray-900 dark:to-amber-950/30 hover:shadow-amber-500/10',
                'icon_bg' => 'bg-amber-100 text-amber-600 dark:bg-amber-900/60 dark:text-amber-300',
                'accent' => 'text-amber-600 dark:text-amber-400',
                'dot' => 'bg-amber-500',
            ],
            'red' => [
                'card' => 'border-rose-200/90 hover:border-rose-400 dark:border-rose-900/60 dark:hover:border-rose-500 bg-gradient-to-br from-white via-white to-rose-50/40 dark:from-gray-900 dark:via-gray-900 dark:to-rose-950/30 hover:shadow-rose-500/10',
                'icon_bg' => 'bg-rose-100 text-rose-600 dark:bg-rose-900/60 dark:text-rose-300',
                'accent' => 'text-rose-600 dark:text-rose-400',
                'dot' => 'bg-rose-500',
            ],
            'cyan' => [
                'card' => 'border-cyan-200/90 hover:border-cyan-400 dark:border-cyan-900/60 dark:hover:border-cyan-500 bg-gradient-to-br from-white via-white to-cyan-50/40 dark:from-gray-900 dark:via-gray-900 dark:to-cyan-950/30 hover:shadow-cyan-500/10',
                'icon_bg' => 'bg-cyan-100 text-cyan-600 dark:bg-cyan-900/60 dark:text-cyan-300',
                'accent' => 'text-cyan-600 dark:text-cyan-400',
                'dot' => 'bg-cyan-500',
            ],
            'indigo' => [
                'card' => 'border-indigo-200/90 hover:border-indigo-400 dark:border-indigo-900/60 dark:hover:border-indigo-500 bg-gradient-to-br from-white via-white to-indigo-50/40 dark:from-gray-900 dark:via-gray-900 dark:to-indigo-950/30 hover:shadow-indigo-500/10',
                'icon_bg' => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/60 dark:text-indigo-300',
                'accent' => 'text-indigo-600 dark:text-indigo-400',
                'dot' => 'bg-indigo-500',
            ],
        ];
    @endphp

    <div class="mb-5">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-2.5 sm:gap-3">
            @foreach ($stats as $key => $stat)
                @php
                    $theme = $colorConfigs[$stat['theme']] ?? $colorConfigs['blue'];
                @endphp

                <a
                    href="{{ $stat['url'] }}"
                    class="group relative flex flex-col justify-between p-3 sm:p-3.5 rounded-xl border shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md cursor-pointer select-none overflow-hidden {{ $theme['card'] }}"
                >
                    <!-- Header with Icon & Indicator -->
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center justify-center w-8 h-8 rounded-lg shrink-0 transition-transform duration-200 group-hover:scale-110 {{ $theme['icon_bg'] }}">
                            <x-filament::icon
                                :icon="$stat['icon']"
                                class="w-4 h-4"
                            />
                        </div>

                        <div class="flex items-center gap-1 opacity-60 group-hover:opacity-100 transition-opacity">
                            <span class="w-1.5 h-1.5 rounded-full {{ $theme['dot'] }}"></span>
                            <x-filament::icon
                                icon="heroicon-m-arrow-up-right"
                                class="w-3 h-3 text-gray-400 group-hover:{{ $theme['accent'] }} transition-colors"
                            />
                        </div>
                    </div>

                    <!-- Metric Value and Label -->
                    <div class="min-w-0">
                        <div class="text-base sm:text-lg font-bold tracking-tight text-gray-950 dark:text-white truncate">
                            {{ $stat['value'] }}
                        </div>
                        <div class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider truncate mt-0.5 group-hover:text-gray-900 dark:group-hover:text-gray-200 transition-colors">
                            {{ $stat['title'] }}
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
