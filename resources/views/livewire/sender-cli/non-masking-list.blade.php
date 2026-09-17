<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="space-y-1">
            <h2 class="text-xl font-semibold tracking-tight text-gray-950 dark:text-white">Non-Masking Sender Numbers</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Numeric sender and CLI numbers only. Pricing is managed elsewhere.</p>
        </div>

        <x-filament::button wire:click="openAssignmentModal">
            Assign sender number
        </x-filament::button>
    </div>

    <x-filament::section>
        <div class="grid gap-3 md:grid-cols-3">
            <x-filament::input.wrapper>
                <x-filament::input wire:model.live.debounce.300ms="search" placeholder="Search sender number or gateway" />
            </x-filament::input.wrapper>

            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="gatewayFilter">
                    <option value="">All gateways</option>

                    @foreach ($gateways as $gateway)
                        <option value="{{ $gateway->id }}">{{ $gateway->name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>

            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="assignmentFilter">
                    <option value="">All assignment states</option>
                    <option value="used">Used / assigned</option>
                    <option value="unused">Unused / unassigned</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
    </x-filament::section>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-white/10">
            <div>
                <p class="text-sm font-semibold text-gray-950 dark:text-white">Sender inventory</p>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ number_format($senders->total()) }} sender numbers found</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                    <tr>
                        <th class="px-5 py-3.5">Sender number</th>
                        <th class="px-5 py-3.5">Gateway</th>
                        <th class="px-5 py-3.5">Customer access</th>
                        <th class="px-5 py-3.5 text-center">Messages</th>
                        <th class="px-5 py-3.5">Availability</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($senders as $sender)
                        <tr class="transition-colors hover:bg-primary-50/30 dark:hover:bg-white/5">
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1 font-mono text-sm font-semibold tracking-tight text-gray-950 dark:bg-white/10 dark:text-white">
                                    {{ $sender->sender_id }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $sender->gateway?->name ?? 'No gateway' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                @if ($sender->users_count === 0)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-warning-50 px-2.5 py-1 text-xs font-semibold text-warning-700 ring-1 ring-inset ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-300">
                                        <span class="size-1.5 rounded-full bg-warning-500"></span>
                                        Unused
                                    </span>
                                @else
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($sender->users->take(2) as $customer)
                                            <span class="inline-flex max-w-44 items-center truncate rounded-full bg-primary-50 px-2.5 py-1 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/15 dark:bg-primary-400/10 dark:text-primary-300">
                                                {{ $customer->name }}
                                            </span>
                                        @endforeach

                                        @if ($sender->users_count > 2)
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                                +{{ $sender->users_count - 2 }} more
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="font-semibold tabular-nums text-gray-700 dark:text-gray-200">{{ number_format($sender->messages_count) }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <span @class([
                                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset',
                                    'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-300' => $sender->is_enabled,
                                    'bg-gray-100 text-gray-600 ring-gray-500/20 dark:bg-white/10 dark:text-gray-300' => ! $sender->is_enabled,
                                ])>
                                    <span @class([
                                        'size-1.5 rounded-full',
                                        'bg-success-500' => $sender->is_enabled,
                                        'bg-gray-400' => ! $sender->is_enabled,
                                    ])></span>
                                    {{ $sender->is_enabled ? 'Enabled' : 'Disabled' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <x-filament::button color="gray" size="sm" wire:click="openAssignmentModal({{ $sender->id }})">
                                    Assign
                                </x-filament::button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center">
                                <p class="font-medium text-gray-700 dark:text-gray-200">No non-masking sender numbers found.</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try a different search or assignment filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($senders->hasPages())
            <div class="border-t border-gray-200 px-5 py-4 dark:border-white/10">
                {{ $senders->links() }}
            </div>
        @endif
    </div>

    <x-filament::modal
        id="sender-assignment-modal"
        heading="Assign sender number"
        description="Choose a sender number and the customer users who may use it. Existing customer access remains available."
        teleport="body"
        width="lg"
        wire:submit.prevent="saveAssignment"
    >
        <div class="space-y-5">
            <div class="space-y-2">
                <label for="sender-number" class="text-sm font-medium text-gray-950 dark:text-white">Sender number</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select id="sender-number" wire:model.live="selectedSenderId">
                        <option value="">Select a sender number</option>

                        @foreach ($availableSenders as $availableSender)
                            <option value="{{ $availableSender->id }}">{{ $availableSender->sender_id }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
                @error('selectedSenderId')
                    <p class="text-sm text-danger-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label for="customer-users" class="text-sm font-medium text-gray-950 dark:text-white">Customer users</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select id="customer-users" multiple size="8" wire:model="selectedCustomerIds">
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->email ? ' — '.$customer->email : '' }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
                <p class="text-xs text-gray-500 dark:text-gray-400">Hold Ctrl or Cmd to choose more than one customer.</p>
                @error('selectedCustomerIds')
                    <p class="text-sm text-danger-600">{{ $message }}</p>
                @enderror
                @error('selectedCustomerIds.*')
                    <p class="text-sm text-danger-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <x-slot name="footer">
            <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'sender-assignment-modal' })">
                Cancel
            </x-filament::button>
            <x-filament::button type="submit">
                Save assignment
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>
