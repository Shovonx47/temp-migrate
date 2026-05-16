@php
    $headers = $get('file_headers') ?? [];
    $sample = $get('file_sample') ?? [];
    $totalRows = (int) ($get('file_total_rows') ?? 0);
@endphp

@if($headers && $sample)
    <div class="space-y-3">
        {{-- File summary --}}
        <div class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
            <div class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-200">
                <x-filament::icon
                    icon="heroicon-o-document-text"
                    class="h-5 w-5 text-gray-500 dark:text-gray-400 shrink-0"
                />
                <div>
                    <span class="font-medium">{{ number_format($totalRows) }}</span>
                    {{ Str::plural('row', $totalRows) }} &middot;
                    <span class="font-medium">{{ count($headers) }}</span>
                    {{ Str::plural('column', count($headers)) }}
                </div>
            </div>
        </div>

        {{-- Preview table --}}
        <div class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
            <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
                <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
                    Preview (first {{ count($sample) }} {{ Str::plural('row', count($sample)) }})
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        @foreach ($headers as $header)
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $header }}
                            </th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-950">
                    @foreach ($sample as $row)
                        <tr>
                            @foreach ($headers as $header)
                                <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                    {{ $row[$header] ?? '' }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if ($totalRows > count($sample))
                <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        + {{ number_format($totalRows - count($sample)) }} more {{ Str::plural('row', $totalRows - count($sample)) }}
                    </p>
                </div>
            @endif
        </div>
    </div>
@endif
