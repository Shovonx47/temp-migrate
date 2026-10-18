@php use Filament\Support\Icons\Heroicon; @endphp
<div wire:poll.30s="refreshBalance">
    <x-filament::button
        type="button"
        x-on:click="$wire.mountAction('deposit')"
        :outlined="true"
        color="gray"
        :icon="Heroicon::OutlinedCurrencyBangladeshi"
    >
        {{ number_format($balance, 2) }}
    </x-filament::button>

    <x-filament-actions::modals/>
</div>
