<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $this->getRecordTitle() }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Customer users with an active assignment to this tariff plan.
                </p>
            </div>

            <span class="inline-flex w-fit items-center rounded-full bg-primary-50 px-3 py-1 text-sm font-semibold text-primary-700 ring-1 ring-inset ring-primary-600/20 dark:bg-primary-400/10 dark:text-primary-300">
                {{ number_format($this->activeCustomerAssignmentsCount) }} active customer{{ $this->activeCustomerAssignmentsCount === 1 ? '' : 's' }}
            </span>
        </div>
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>
