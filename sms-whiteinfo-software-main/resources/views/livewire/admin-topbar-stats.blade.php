@php
    $configs = [
        'kyc_pending' => [
            'bg'     => '#7c3aed', // Purple
            'border' => '#6d28d9',
        ],
        'today_deposit' => [
            'bg'     => '#059669', // Green
            'border' => '#047857',
        ],
        'today_sms' => [
            'bg'     => '#0284c7', // Blue
            'border' => '#0369a1',
        ],
        'today_profit' => [
            'bg'     => '#d97706', // Orange
            'border' => '#b45309',
        ],
        'low_balance' => [
            'bg'     => '#e11d48', // Red
            'border' => '#be123c',
        ],
        'open_tickets' => [
            'bg'     => '#0891b2', // Cyan
            'border' => '#0e7490',
        ],
        'ip_whitelist' => [
            'bg'     => '#4f46e5', // Indigo
            'border' => '#4338ca',
        ],
    ];
@endphp

@if (($mode ?? 'desktop') === 'desktop')
    {{-- Desktop Topbar View: Inlined in topbar beside search --}}
    <div
        wire:poll.60s
        class="hidden md:flex items-center gap-1 xl:gap-1.5 py-0.5 overflow-x-auto no-scrollbar shrink-0"
        style="scrollbar-width: none; -ms-overflow-style: none;"
    >
        @foreach ($stats as $key => $stat)
            @php
                $color = $configs[$key] ?? ['bg' => '#374151', 'border' => '#1f2937'];
            @endphp

            <a
                href="{{ $stat['url'] }}"
                title="{{ $stat['title'] }}: {{ $stat['value'] }}"
                style="background-color: {{ $color['bg'] }}; border: 1px solid {{ $color['border'] }}; color: #ffffff;"
                class="group inline-flex items-center gap-1 px-1.5 py-0.5 xl:px-2 xl:py-1 rounded-md text-xs shrink-0 transition-all duration-150 hover:brightness-110 hover:scale-[1.02] active:scale-[0.98] shadow-xs cursor-pointer select-none"
            >
                <x-filament::icon
                    :icon="$stat['icon']"
                    class="w-3.5 h-3.5 shrink-0 text-white"
                    style="color: #ffffff;"
                />

                {{-- Full title on large screens, short title on medium screens to prevent truncation --}}
                <span
                    class="text-[11px] font-medium tracking-tight whitespace-nowrap hidden 2xl:inline"
                    style="color: rgba(255, 255, 255, 0.95);"
                >
                    {{ $stat['title'] }}:
                </span>
                <span
                    class="text-[11px] font-medium tracking-tight whitespace-nowrap 2xl:hidden"
                    style="color: rgba(255, 255, 255, 0.95);"
                >
                    {{ $stat['short_title'] }}:
                </span>

                <span
                    class="text-xs font-extrabold tracking-tight whitespace-nowrap"
                    style="color: #ffffff;"
                >
                    {{ $stat['value'] }}
                </span>
            </a>
        @endforeach
    </div>
@else
    {{-- Mobile Topbar View: Full-width responsive swipeable ribbon beneath topbar on phones/tablets --}}
    <div
        wire:poll.60s
        class="md:hidden block w-full bg-white/95 dark:bg-gray-900/95 border-b border-gray-200/80 dark:border-white/10 px-2.5 py-1.5 shadow-xs overflow-x-auto no-scrollbar scroll-smooth"
        style="scrollbar-width: none; -ms-overflow-style: none;"
    >
        <div class="flex items-center gap-1.5 min-w-max">
            @foreach ($stats as $key => $stat)
                @php
                    $color = $configs[$key] ?? ['bg' => '#374151', 'border' => '#1f2937'];
                @endphp

                <a
                    href="{{ $stat['url'] }}"
                    title="{{ $stat['title'] }}: {{ $stat['value'] }}"
                    style="background-color: {{ $color['bg'] }}; border: 1px solid {{ $color['border'] }}; color: #ffffff;"
                    class="group inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs shrink-0 transition-all duration-150 active:scale-[0.98] shadow-xs cursor-pointer select-none"
                >
                    <x-filament::icon
                        :icon="$stat['icon']"
                        class="w-3.5 h-3.5 shrink-0 text-white"
                        style="color: #ffffff;"
                    />

                    <span
                        class="text-[11px] font-medium tracking-tight whitespace-nowrap"
                        style="color: rgba(255, 255, 255, 0.95);"
                    >
                        {{ $stat['title'] }}:
                    </span>

                    <span
                        class="text-xs font-extrabold tracking-tight whitespace-nowrap"
                        style="color: #ffffff;"
                    >
                        {{ $stat['value'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>
@endif
