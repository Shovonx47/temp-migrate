@props([
    'position' => null,
])

@php
    use Filament\Actions\Action;
    use Filament\Enums\UserMenuPosition;
    use Illuminate\Support\Arr;
    use App\Enums\UserStatus;
    use App\Enums\UserType;

    /** @var \App\Models\User|null $user */
    $user = filament()->auth()->user();

    $items = $this->getUserMenuItems();

    $itemsBeforeAndAfterThemeSwitcher = collect($items)
        ->groupBy(fn (Action $item): bool => $item->getSort() < 0, preserveKeys: true)
        ->all();
    $itemsBeforeThemeSwitcher = $itemsBeforeAndAfterThemeSwitcher[true] ?? collect();
    $itemsAfterThemeSwitcher = $itemsBeforeAndAfterThemeSwitcher[false] ?? collect();

    $hasProfileHeader = $itemsBeforeThemeSwitcher->has('profile') &&
        blank(($item = Arr::first($itemsBeforeThemeSwitcher))->getUrl()) &&
        (! $item->hasAction());

    if ($itemsBeforeThemeSwitcher->has('profile')) {
        $itemsBeforeThemeSwitcher = $itemsBeforeThemeSwitcher->prepend($itemsBeforeThemeSwitcher->pull('profile'), 'profile');
    }

    $position ??= filament()->getUserMenuPosition();
    $isSidebarCollapsibleOnDesktop = filament()->isSidebarCollapsibleOnDesktop();

    // User details & formatting
    $status = $user?->status;
    $type   = $user?->type;

    $statusConfig = [
        'pending'  => [
            'bg'    => 'bg-amber-500/10 dark:bg-amber-500/20 border-amber-500/20 dark:border-amber-500/30',
            'dot'   => 'bg-amber-500 ring-amber-400/30',
            'text'  => 'text-amber-700 dark:text-amber-400',
            'label' => 'Pending Approval',
            'icon'  => 'heroicon-m-clock',
        ],
        'approved' => [
            'bg'    => 'bg-emerald-500/10 dark:bg-emerald-500/20 border-emerald-500/20 dark:border-emerald-500/30',
            'dot'   => 'bg-emerald-500 ring-emerald-400/30',
            'text'  => 'text-emerald-700 dark:text-emerald-400',
            'label' => 'Approved / Active',
            'icon'  => 'heroicon-m-check-badge',
        ],
        'rejected' => [
            'bg'    => 'bg-rose-500/10 dark:bg-rose-500/20 border-rose-500/20 dark:border-rose-500/30',
            'dot'   => 'bg-rose-500 ring-rose-400/30',
            'text'  => 'text-rose-700 dark:text-rose-400',
            'label' => 'Application Rejected',
            'icon'  => 'heroicon-m-x-circle',
        ],
        'banned'   => [
            'bg'    => 'bg-red-500/10 dark:bg-red-500/20 border-red-500/20 dark:border-red-500/30',
            'dot'   => 'bg-red-600 ring-red-400/30',
            'text'  => 'text-red-700 dark:text-red-400',
            'label' => 'Account Banned',
            'icon'  => 'heroicon-m-no-symbol',
        ],
    ];

    $sc = $statusConfig[$status?->value ?? 'pending'] ?? $statusConfig['pending'];

    // Role configuration
    $isSuperman = $type === UserType::Superman;
    $isSp       = $type === UserType::Sp;
    $isCustomer = $type === UserType::Customer;

    if ($isSuperman) {
        $roleLabel = 'Super Admin';
        $roleBadgeBg = 'bg-rose-500/10 text-rose-600 border-rose-500/20 dark:bg-rose-500/20 dark:text-rose-300';
        $roleIcon = 'heroicon-m-shield-check';
    } elseif ($isSp) {
        $roleLabel = 'Service Provider';
        $roleBadgeBg = 'bg-indigo-500/10 text-indigo-600 border-indigo-500/20 dark:bg-indigo-500/20 dark:text-indigo-300';
        $roleIcon = 'heroicon-m-building-office-2';
    } else {
        $roleLabel = 'Verified Customer';
        $roleBadgeBg = 'bg-teal-500/10 text-teal-700 border-teal-500/20 dark:bg-teal-500/20 dark:text-teal-300';
        $roleIcon = 'heroicon-m-check-badge';
    }

    // Avatar initials
    $nameParts = array_filter(explode(' ', trim($user?->name ?? '')));
    $initials = collect($nameParts)
        ->map(fn ($p) => strtoupper(mb_substr($p, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

{{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_BEFORE) }}

<x-filament::dropdown
    :placement="($position === UserMenuPosition::Topbar) ? 'bottom-end' : 'top-end'"
    :teleport="$position === UserMenuPosition::Topbar"
    :width="'md'"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($attributes)
            ->class(['fi-user-menu'])
    "
>
    {{-- Trigger: Clean avatar button only on topbar navigation --}}
    <x-slot name="trigger">
        @if ($position === UserMenuPosition::Topbar)
            <button
                aria-label="{{ __('filament-panels::layout.actions.open_user_menu.label') }}"
                type="button"
                class="fi-user-menu-trigger group relative rounded-full p-0.5 focus:outline-none focus:ring-2 focus:ring-teal-500/50 transition duration-200"
            >
                <div class="relative">
                    <x-filament-panels::avatar.user :user="$user" loading="lazy" class="ring-2 ring-gray-200/80 dark:ring-gray-700 group-hover:ring-teal-500 transition shadow-xs" />
                    {{-- Small online indicator on avatar --}}
                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full {{ $status?->value === 'approved' ? 'bg-emerald-500' : 'bg-amber-500' }} ring-2 ring-white dark:ring-gray-900 shadow-xs"></span>
                </div>
            </button>
        @else
            <button
                aria-label="{{ __('filament-panels::layout.actions.open_user_menu.label') }}"
                type="button"
                class="fi-user-menu-trigger flex items-center gap-3 w-full p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800/80 transition group"
            >
                <div class="relative shrink-0">
                    <x-filament-panels::avatar.user :user="$user" loading="lazy" class="ring-1 ring-gray-200 dark:ring-gray-700" />
                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full {{ $status?->value === 'approved' ? 'bg-emerald-500' : 'bg-amber-500' }} ring-2 ring-white dark:ring-gray-900"></span>
                </div>

                <div
                    @if ($isSidebarCollapsibleOnDesktop)
                        x-show="$store.sidebar.isOpen"
                    @endif
                    class="fi-user-menu-trigger-text text-left min-w-0 flex-1"
                >
                    <span class="block text-sm font-semibold text-gray-800 dark:text-gray-200 truncate group-hover:text-teal-600 dark:group-hover:text-teal-400 transition">
                        {{ filament()->getUserName($user) }}
                    </span>
                    <span class="block text-[11px] text-gray-500 dark:text-gray-400 truncate">
                        {{ $user?->email }}
                    </span>
                </div>

                {{
                    \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::ChevronUp, alias: \Filament\View\PanelsIconAlias::USER_MENU_TOGGLE_BUTTON, attributes: new \Illuminate\View\ComponentAttributeBag([
                        'x-show' => $isSidebarCollapsibleOnDesktop ? '$store.sidebar.isOpen' : null,
                        'class' => 'w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition shrink-0',
                    ]))
                }}
            </button>
        @endif
    </x-slot>

    {{-- ========================================================
         DROPDOWN HEADER: Comprehensive, Beautiful User Info Card
         ======================================================== --}}
    <div class="p-4 bg-gradient-to-b from-teal-50/60 via-white to-white dark:from-gray-800/90 dark:via-gray-900/95 dark:to-gray-900 border-b border-gray-100 dark:border-gray-800/80">
        {{-- User Identity Row --}}
        <div class="flex items-start gap-3">
            {{-- Big Avatar Initial Circle with Glow --}}
            <div class="relative shrink-0 w-12 h-12 rounded-2xl bg-gradient-to-br from-teal-500 via-teal-600 to-emerald-600 p-0.5 shadow-md shadow-teal-500/15">
                <div class="w-full h-full rounded-[14px] bg-white/10 backdrop-blur-xs flex items-center justify-center border border-white/30 text-white font-black text-base tracking-wider select-none">
                    {{ $initials ?: 'U' }}
                </div>
                {{-- Status indicator dot --}}
                <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full {{ $sc['dot'] }} ring-2 ring-white dark:ring-gray-900 shadow-xs flex items-center justify-center">
                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping opacity-75"></span>
                </span>
            </div>

            {{-- Name and Badges --}}
            <div class="min-w-0 flex-1">
                <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate tracking-tight leading-snug">
                    {{ $user?->name ?? 'User' }}
                </h4>

                <div class="flex flex-wrap items-center gap-1.5 mt-1">
                    {{-- Role Badge --}}
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $roleBadgeBg }} shadow-2xs">
                        <x-filament::icon :icon="$roleIcon" class="w-3 h-3 shrink-0" />
                        <span>{{ $roleLabel }}</span>
                    </span>

                    {{-- Account Status Badge --}}
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $sc['bg'] }} {{ $sc['text'] }} shadow-2xs">
                        <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }} shrink-0"></span>
                        <span>{{ $sc['label'] }}</span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Contact Details Box --}}
        <div class="mt-3.5 pt-3 border-t border-gray-100 dark:border-gray-800/80 space-y-1.5">
            {{-- Email --}}
            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                <div class="w-5 h-5 rounded-md bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 dark:text-gray-500 shrink-0">
                    <x-filament::icon icon="heroicon-m-envelope" class="w-3 h-3" />
                </div>
                <span class="truncate font-medium text-[11.5px] select-all font-mono" title="{{ $user?->email }}">
                    {{ $user?->email ?? 'No email set' }}
                </span>
            </div>

            {{-- Mobile Phone --}}
            @if ($user?->phone)
                <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                    <div class="w-5 h-5 rounded-md bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 dark:text-gray-500 shrink-0">
                        <x-filament::icon icon="heroicon-m-phone" class="w-3 h-3" />
                    </div>
                    <span class="truncate font-medium text-[11.5px] select-all font-mono">
                        {{ $user->phone }}
                    </span>
                </div>
            @endif

            {{-- Account / Wallet Balance (if Customer) --}}
            @if (isset($user->balance))
                <div class="mt-2 pt-2 border-t border-dashed border-gray-200/80 dark:border-gray-800 flex items-center justify-between">
                    <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400">Available Balance:</span>
                    <span class="text-xs font-bold text-teal-600 dark:text-teal-400">
                        BDT {{ number_format((float) $user->balance, 2) }}
                    </span>
                </div>
            @endif
        </div>
    </div>

    {{-- ========================================================
         DROPDOWN ACTIONS: Profile, Theme, and Sign Out
         ======================================================== --}}
    @if ($hasProfileHeader)
        @php
            $item = $itemsBeforeThemeSwitcher['profile'];
            $itemColor = $item->getColor();
            $itemIcon = $item->getIcon();

            unset($itemsBeforeThemeSwitcher['profile']);
        @endphp

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_PROFILE_BEFORE) }}

        <x-filament::dropdown.header :color="$itemColor" :icon="$itemIcon">
            {{ $item->getLabel() }}
        </x-filament::dropdown.header>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_PROFILE_AFTER) }}
    @endif

    {{-- Items before theme switcher (Profile) --}}
    @if ($itemsBeforeThemeSwitcher->isNotEmpty())
        <div class="p-1.5">
            <x-filament::dropdown.list class="space-y-0.5">
                @foreach ($itemsBeforeThemeSwitcher as $key => $item)
                    @if ($key === 'profile')
                        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_PROFILE_BEFORE) }}

                        <div class="fi-user-menu-item-profile">
                            {{ $item }}
                        </div>

                        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_PROFILE_AFTER) }}
                    @else
                        {{ $item }}
                    @endif
                @endforeach
            </x-filament::dropdown.list>
        </div>
    @endif

    {{-- Modern Theme Switcher Container --}}
    @if (filament()->hasDarkMode() && (! filament()->hasDarkModeForced()))
        <div class="px-3 py-2 border-t border-b border-gray-100 dark:border-gray-800/80 bg-gray-50/50 dark:bg-gray-800/30">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                    <x-filament::icon icon="heroicon-m-swatch" class="w-3.5 h-3.5 text-gray-400" />
                    <span>Theme</span>
                </span>
                <div class="shrink-0">
                    <x-filament-panels::theme-switcher />
                </div>
            </div>
        </div>
    @endif

    {{-- Items after theme switcher (Sign Out / Logout) --}}
    @if ($itemsAfterThemeSwitcher->isNotEmpty())
        <div class="p-1.5 bg-gray-50/30 dark:bg-gray-900/40">
            <x-filament::dropdown.list class="space-y-0.5">
                @foreach ($itemsAfterThemeSwitcher as $key => $item)
                    @if ($key === 'profile')
                        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_PROFILE_BEFORE) }}

                        {{ $item }}

                        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_PROFILE_AFTER) }}
                    @else
                        <div class="fi-user-menu-item-logout">
                            {{ $item }}
                        </div>
                    @endif
                @endforeach
            </x-filament::dropdown.list>
        </div>
    @endif
</x-filament::dropdown>

{{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_AFTER) }}
