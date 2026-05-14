<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Add funds to your account
        </x-slot>

        <x-slot name="description">
            You will be redirected to PipraPay to complete the payment securely.
        </x-slot>

        {{ $this->form }}

        <x-filament::button
            wire:click="submit"
            color="primary"
            class="mt-4 w-full"
            icon="heroicon-o-banknotes">
            Proceed to Payment
        </x-filament::button>
    </x-filament::section>
</x-filament-panels::page>
