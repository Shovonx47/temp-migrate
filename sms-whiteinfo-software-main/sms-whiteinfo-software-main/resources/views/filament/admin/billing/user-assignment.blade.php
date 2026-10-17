<x-filament-panels::page>
    {{-- Customer Selector --}}
    <x-filament::section>
        <x-slot name="heading">Select Customer</x-slot>
        <x-slot name="description">Search for a customer to view and manage their tariff plan assignment.</x-slot>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <x-filament::input.wrapper>
                    <x-filament::input.select
                        wire:model.live="selectedCustomerId"
                        id="customer-select"
                    >
                        <option value="">— Select a customer —</option>
                        @foreach(\App\Models\User::customers()->orderBy('name')->get() as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->email }})</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>
    </x-filament::section>

    @if($this->selectedCustomerId)
        @php
            $currentAssignment = $this->getCurrentAssignment();
            $customer = $this->getSelectedCustomer();
        @endphp

        {{-- Current Assignment --}}
        <x-filament::section>
            <x-slot name="heading">Current Tariff Plan</x-slot>

            @if($currentAssignment)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Plan Name</p>
                        <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">
                            {{ $currentAssignment->tariffPlan?->name ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Plan Type</p>
                        <p class="mt-1 text-base text-gray-900 dark:text-white">
                            {{ $currentAssignment->tariffPlan?->type?->getLabel() ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Effective From</p>
                        <p class="mt-1 text-base text-gray-900 dark:text-white">
                            {{ $currentAssignment->effective_from?->format('d M Y') ?? '—' }}
                        </p>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                    No tariff plan assigned. Billing resolves through legacy operator rate overrides or system defaults.
                </p>
            @endif
        </x-filament::section>

        {{-- Assign New Plan --}}
        <x-filament::section>
            <x-slot name="heading">Assign / Change Plan</x-slot>
            <x-slot name="description">
                Selecting a new plan will immediately supersede the current one. The effective date controls when billing switches.
            </x-slot>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div>
                    <label class="fi-fo-field-wrp-label block text-sm font-medium leading-6 text-gray-950 dark:text-white mb-1">
                        New Tariff Plan <span class="text-danger-600">*</span>
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model="newPlanId" id="new-plan-select">
                            <option value="">— Select a plan —</option>
                            @foreach(\App\Models\TariffPlan::active()->orderBy('name')->get() as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} ({{ $plan->type->getLabel() }})</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <label class="fi-fo-field-wrp-label block text-sm font-medium leading-6 text-gray-950 dark:text-white mb-1">
                        Effective From <span class="text-danger-600">*</span>
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="date"
                            wire:model="effectiveFrom"
                            id="effective-from-input"
                            value="{{ now()->toDateString() }}"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <label class="fi-fo-field-wrp-label block text-sm font-medium leading-6 text-gray-950 dark:text-white mb-1">
                        Notes (optional)
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            wire:model="assignmentNotes"
                            id="assignment-notes-input"
                            placeholder="e.g. Upgraded to standard plan per request"
                        />
                    </x-filament::input.wrapper>
                </div>
            </div>

            <div class="mt-4">
                <x-filament::button
                    wire:click="assignPlan"
                    wire:confirm="Are you sure you want to reassign this customer's tariff plan? This directly affects their billing."
                    color="primary"
                    icon="heroicon-o-check-circle"
                    id="assign-plan-button"
                >
                    Assign Plan
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- Assignment History --}}
        <x-filament::section>
            <x-slot name="heading">Assignment History</x-slot>
            <x-slot name="description">All past and current plan assignments for this customer.</x-slot>

            {{ $this->table }}
        </x-filament::section>
    @endif
</x-filament-panels::page>
