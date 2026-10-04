<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Info banner --}}
        <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950 p-5">
            <div class="flex items-start gap-3">
                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" />
                <div class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <p class="font-semibold">How KYC Permissions Work</p>
                    <ul class="list-disc list-inside space-y-1 text-xs">
                        <li><strong>Super Admin</strong> = any admin NOT listed in the table below. They have ALL permissions automatically.</li>
                        <li><strong>Restricted Admin</strong> = any admin listed in the table below. They can only do what you explicitly grant.</li>
                        <li>To make an admin unrestricted (Super Admin again), click <em>"Make Super Admin"</em> to remove their permission row.</li>
                        <li>To restrict an admin, use the form below to select them and check only what they're allowed to do.</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Permission matrix reference --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wide">Permission Reference</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach (\App\Enums\KycPermission::groups() as $groupName => $perms)
                    <div>
                        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">{{ $groupName }}</p>
                        @foreach ($perms as $perm)
                            <div class="flex items-center gap-2 py-1">
                                <span class="text-xs px-2 py-0.5 rounded-full
                                    @if(in_array($perm->getColor(), ['success'])) bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300
                                    @elseif(in_array($perm->getColor(), ['danger'])) bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300
                                    @elseif(in_array($perm->getColor(), ['warning'])) bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300
                                    @else bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 @endif">
                                    {{ $perm->getLabel() }}
                                </span>
                                <code class="text-xs text-gray-400 dark:text-gray-500">{{ $perm->value }}</code>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Grant Form --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 uppercase tracking-wide">Grant / Update Permissions</h3>
            <form wire:submit="grantPermissions">
                {{ $this->form }}
                <div class="mt-4">
                    <x-filament::button type="submit" color="primary" icon="heroicon-o-shield-check">
                        Save Permissions
                    </x-filament::button>
                </div>
            </form>
        </div>

        {{-- Current grants table --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            {{ $this->table }}
        </div>

    </div>
</x-filament-panels::page>
