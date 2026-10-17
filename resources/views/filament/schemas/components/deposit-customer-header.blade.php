@php
    /** @var \App\Models\User|null $customer */
    $customer = $customer ?? (isset($getRecord) ? $getRecord() : null) ?? (isset($get) && $get('customer_id') ? \App\Models\User::find($get('customer_id')) : null);
@endphp

@if ($customer)
    <div class="flex items-center justify-between rounded-lg border border-gray-200/80 bg-gray-50/80 px-3 py-2 text-xs dark:border-gray-800 dark:bg-gray-900/60">
        <div class="flex items-center gap-1.5 truncate">
            <span class="text-gray-500 dark:text-gray-400">Customer:</span>
            <span class="font-bold text-gray-900 dark:text-gray-100 truncate">{{ $customer->name }}</span>
        </div>
        <div class="flex items-center gap-1.5 shrink-0 pl-2">
            <span class="text-gray-500 dark:text-gray-400">Current Balance:</span>
            <span class="font-mono font-bold text-gray-900 dark:text-gray-100">BDT {{ number_format((float) $customer->balance, 2) }}</span>
        </div>
    </div>
@endif
