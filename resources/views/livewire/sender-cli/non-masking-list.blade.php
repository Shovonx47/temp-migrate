<div class="space-y-6">
    <x-filament::section heading="Non-Masking Sender Numbers" description="Numeric sender/CLI numbers only. This list does not manage prices or rates.">
        <div class="grid gap-3 md:grid-cols-3">
            <x-filament::input.wrapper><x-filament::input wire:model.live.debounce.300ms="search" placeholder="Search sender number or gateway" /></x-filament::input.wrapper>
            <x-filament::input.wrapper><x-filament::input.select wire:model.live="gatewayFilter"><option value="">All gateways</option>@foreach($gateways as $gateway)<option value="{{ $gateway->id }}">{{ $gateway->name }}</option>@endforeach</x-filament::input.select></x-filament::input.wrapper>
            <x-filament::input.wrapper><x-filament::input.select wire:model.live="assignmentFilter"><option value="">All assignment states</option><option value="unused">Unused / unassigned</option><option value="assigned">Assigned to customer</option></x-filament::input.select></x-filament::input.wrapper>
        </div>
    </x-filament::section>
    <x-filament::section>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b text-xs uppercase text-gray-500"><tr><th class="p-3">Sender Number</th><th class="p-3">Gateway</th><th class="p-3">Assignment</th><th class="p-3">Messages</th><th class="p-3">Status</th></tr></thead><tbody class="divide-y">@forelse($senders as $sender)<tr><td class="p-3 font-mono font-semibold">{{ $sender->sender_id }}</td><td class="p-3">{{ $sender->gateway?->name ?? '—' }}</td><td class="p-3">@if($sender->users_count === 0)<span class="text-warning-600">Unused</span>@else {{ $sender->users->pluck('name')->join(', ') }} @endif</td><td class="p-3">{{ number_format($sender->messages_count) }}</td><td class="p-3">{{ $sender->is_enabled ? 'Enabled' : 'Disabled' }}</td></tr>@empty<tr><td colspan="5" class="p-8 text-center text-gray-500">No non-masking sender numbers found.</td></tr>@endforelse</tbody></table></div>
        <div class="mt-4">{{ $senders->links() }}</div>
    </x-filament::section>
</div>
