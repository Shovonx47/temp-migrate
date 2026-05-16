<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Operator rates</x-slot>
        <x-slot name="description">
            Per-part rates applied when you send to each operator. "Overridden" means your account has a custom rate;
            otherwise the system-default rate is used. Contact support if you need a different rate.
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="py-2 pr-4">Country</th>
                        <th class="py-2 pr-4">Operator</th>
                        <th class="py-2 pr-4">Codes</th>
                        <th class="py-2 pr-4 text-right">Non-masking</th>
                        <th class="py-2 pr-4 text-right">Masking</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($this->rows as $row)
                        <tr>
                            <td class="py-2 pr-4">{{ $row['country'] }}</td>
                            <td class="py-2 pr-4 font-medium text-gray-800 dark:text-gray-100">{{ $row['name'] }}</td>
                            <td class="py-2 pr-4 text-gray-500">{{ $row['codes'] }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">
                                @if ($row['non_masking'] !== null)
                                    ৳{{ number_format($row['non_masking'], 4) }}
                                    @if ($row['overridden_non_masking'])
                                        <span class="ml-1 inline-flex items-center rounded bg-primary-50 px-1.5 py-0.5 text-[10px] font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">
                                            override
                                        </span>
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="py-2 pr-4 text-right tabular-nums">
                                @if ($row['masking'] !== null)
                                    ৳{{ number_format($row['masking'], 4) }}
                                    @if ($row['overridden_masking'])
                                        <span class="ml-1 inline-flex items-center rounded bg-primary-50 px-1.5 py-0.5 text-[10px] font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">
                                            override
                                        </span>
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-gray-500">No operators configured yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
