@php
    /** @var \App\Models\User $user */
    $user   = filament()->auth()->user();
    $status = $user?->status;
    $type   = $user?->type;

    $statusColors = [
        'pending'  => ['bg' => 'bg-amber-100 dark:bg-amber-900/40',  'dot' => 'bg-amber-500',  'text' => 'text-amber-700 dark:text-amber-400',  'label' => 'Pending Approval'],
        'approved' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/40', 'dot' => 'bg-emerald-500', 'text' => 'text-emerald-700 dark:text-emerald-400', 'label' => 'Active'],
        'rejected' => ['bg' => 'bg-red-100 dark:bg-red-900/40',      'dot' => 'bg-red-500',     'text' => 'text-red-700 dark:text-red-400',      'label' => 'Rejected'],
        'banned'   => ['bg' => 'bg-red-100 dark:bg-red-900/40',      'dot' => 'bg-red-500',     'text' => 'text-red-700 dark:text-red-400',      'label' => 'Banned'],
    ];

    $typeColors = [
        'superman' => 'text-red-600 dark:text-red-400',
        'customer' => 'text-teal-600 dark:text-teal-400',
        'sp'       => 'text-amber-600 dark:text-amber-400',
    ];

    $sc = $statusColors[$status?->value ?? 'pending'] ?? $statusColors['pending'];
    $tc = $typeColors[$type?->value ?? 'customer']    ?? $typeColors['customer'];

    // Avatar initials from full name
    $initials = collect(explode(' ', $user?->name ?? ''))
        ->map(fn ($p) => strtoupper(mb_substr($p, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

{{-- Rich user card injected above the Filament user-menu items --}}
<div class="px-3 pt-3 pb-2.5 border-b border-gray-200 dark:border-white/10">

    {{-- Avatar + name row --}}
    <div class="flex items-center gap-3 mb-3">
        {{-- Avatar circle with gradient --}}
        <div class="relative shrink-0 w-11 h-11 rounded-full bg-gradient-to-br from-teal-500 to-teal-700 flex items-center justify-center shadow-sm ring-2 ring-white dark:ring-gray-900">
            <span class="text-sm font-bold text-white tracking-wide select-none">{{ $initials ?: 'U' }}</span>
            {{-- Online status dot --}}
            <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full bg-emerald-500 border-2 border-white dark:border-gray-900 shadow-sm"></span>
        </div>

        {{-- Name + role --}}
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate leading-tight">
                {{ $user?->name ?? 'User' }}
            </p>
            <div class="flex items-center gap-1 mt-0.5">
                <x-filament::icon
                    :icon="$type?->getIcon() ?? 'heroicon-o-user'"
                    class="w-3 h-3 shrink-0 {{ $tc }}"
                />
                <span class="text-xs font-medium {{ $tc }} truncate">
                    {{ $type?->getLabel() ?? 'Customer' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Info rows --}}
    <div class="space-y-1.5">

        {{-- Email --}}
        <div class="flex items-center gap-2 min-w-0">
            <x-filament::icon
                icon="heroicon-o-envelope"
                class="w-3.5 h-3.5 shrink-0 text-gray-400 dark:text-gray-500"
            />
            <span class="text-xs text-gray-500 dark:text-gray-400 truncate font-mono">
                {{ $user?->email ?? '—' }}
            </span>
        </div>

        {{-- Phone (if available) --}}
        @if ($user?->phone)
        <div class="flex items-center gap-2 min-w-0">
            <x-filament::icon
                icon="heroicon-o-phone"
                class="w-3.5 h-3.5 shrink-0 text-gray-400 dark:text-gray-500"
            />
            <span class="text-xs text-gray-500 dark:text-gray-400 truncate font-mono">
                {{ $user->phone }}
            </span>
        </div>
        @endif

        {{-- Account status badge --}}
        <div class="flex items-center gap-2">
            <x-filament::icon
                icon="heroicon-o-shield-check"
                class="w-3.5 h-3.5 shrink-0 text-gray-400 dark:text-gray-500"
            />
            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $sc['bg'] }} {{ $sc['text'] }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }} shrink-0 animate-pulse"></span>
                {{ $sc['label'] }}
            </span>
        </div>

    </div>
</div>
