@php
    $source = $get('source') ?? 'manual';
    $body = $get('body') ?? '';
    $bodyVariables = $get('body_variables') ?? [];

    if ($source === 'upload') {
        $headers = $get('file.headers') ?? [];
        $firstRow = ($get('file.rows') ?? [])[0] ?? [];
        $padded = array_pad($firstRow, count($headers), '');
        $sample = array_combine($headers, $padded);
        $options = $headers;
    } else {
        $options = [];
        $sample = [];
    }
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="mappingComponent(@js([
            'source' => $source,
            'options' => $options,
            'sample' => $sample,
            'body' => $body,
            'bodyVariables' => $bodyVariables,
            'state' => $getState() ?? [],
        ]))"
        x-on:mapping-updated.window="
            $wire.set('{{ $getStatePath() }}', $event.detail)
        "
        class="space-y-4"
    >
        <div x-show="showComponent" x-cloak class="space-y-4">
            {{-- Phone mapping --}}
            <div class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
                <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
                    <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Phone Column</label>
                </div>
                <div class="px-4 py-3 bg-white dark:bg-gray-950">
                    <select
                        x-model="phone"
                        x-on:change="resolve()"
                        class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 py-2 px-3"
                    >
                        <option value="">Select phone column...</option>
                        @foreach ($options as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Variable mappings --}}
            <div
                x-show="bodyVariables.length > 0"
                x-cloak
                class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden"
            >
                <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Variable Mappings</p>
                </div>
                <div class="px-4 py-3 bg-white dark:bg-gray-950 space-y-3">
                    <template x-for="variable in bodyVariables" :key="variable">
                        <div class="flex items-center gap-3">
                            <label
                                class="text-sm font-medium text-primary-600 dark:text-primary-400 w-24 shrink-0"
                                x-text="'{' + variable + '}'"
                            ></label>
                            <select
                                x-model="variableMappings[variable]"
                                x-on:change="resolve()"
                                class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 py-2 px-3"
                            >
                                <option value="">Select column...</option>
                                @foreach ($options as $opt)
                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Preview --}}
            <div x-show="body || phone" x-cloak class="space-y-3">
                <div class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
                    <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Message Preview</p>
                    </div>
                    <div class="px-4 py-3 bg-white dark:bg-gray-950">
                        <p class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap" x-text="resolvedBody"></p>
                    </div>
                </div>

                <div class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
                    <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Phone Mapping</p>
                    </div>
                    <div class="px-4 py-3 bg-white dark:bg-gray-950 flex items-center gap-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Column "<span x-text="phone || '&mdash;'"></span>" &rarr;</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="resolvedPhone"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
