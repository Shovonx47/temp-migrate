@php
    $record = $getRecord();
@endphp

@if ($record)
    <div class="py-1 whitespace-nowrap">
        <x-filament::button
            wire:click="mountTableAction('impersonate', '{{ $record->getKey() }}')"
            size="xs"
            color="gray"
            icon="heroicon-m-arrow-right-end-on-rectangle"
            outlined
        >
            Login As
        </x-filament::button>
    </div>
@endif
