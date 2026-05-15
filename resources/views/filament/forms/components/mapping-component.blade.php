@php
    $source = $get('source') ?? 'manual';
    $body = $get('body') ?? '';
    $variables = $get('variables') ?? [];

    if ($source === 'upload') {
        $headers = $get('file.headers') ?? [];
        $firstRow = ($get('file.rows') ?? [])[0] ?? [];
        $padded = array_pad($firstRow, count($headers), '');
        $sample = array_combine($headers, $padded);
        $options = $headers;
    } elseif ($source === 'group') {
        $groupIds = $get('groups') ?? [];
        $firstContact = \App\Models\Contact::query()
            ->whereHas('groups', fn ($q) => $q->whereIn('groups.id', $groupIds))
            ->where('is_blocked', false)
            ->first();

        $metaKeys = [];
        if ($firstContact?->meta) {
            $metaKeys = array_map(fn (string $k) => "meta.{$k}", array_keys($firstContact->meta));
        }

        $options = array_merge(['name'], $metaKeys);
        $sample = [
            'name' => (string) ($firstContact?->name ?? ''),
            'phone' => (string) ($firstContact?->phone ?? ''),
        ];
        foreach ($metaKeys as $key) {
            $sample[$key] = (string) ($firstContact?->meta[substr($key, 5)] ?? '');
        }
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
            'variables' => $variables,
            'state' => $getState() ?? [],
        ]))"
        x-on:mapping-updated.window="
            $wire.set('{{ $getStatePath() }}', $event.detail)
        "
        class="space-y-4"
    >
        {{-- Manual source: no mapping needed --}}
        <div
            x-show="!showComponent"
            x-cloak
            class="flex flex-col items-center justify-center px-6 py-10 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl"
        >
            <x-filament::icon
                icon="heroicon-o-check-circle"
                class="h-10 w-10 text-gray-400 dark:text-gray-500 mb-3"
            />
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                No mapping needed
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Recipients entered manually &mdash; nothing to map.
            </p>
        </div>

        {{-- Upload / Group: mapping UI --}}
        <div x-show="showComponent" x-cloak class="space-y-4">
            {{-- Phone mapping --}}
            <div class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
                <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
                    <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Phone Column</label>
                </div>
                <div class="px-4 py-3 bg-white dark:bg-gray-950">
                    <template x-if="isGroup">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Using contact phone number
                        </p>
                    </template>
                    <template x-if="!isGroup">
                        <select
                            x-model="phone"
                            x-on:change="resolve()"
                            class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 py-2 px-3"
                        >
                            <option value="">Select phone column...</option>
                            <template x-for="opt in options" :key="opt">
                                <option :value="opt" x-text="opt"></option>
                            </template>
                        </select>
                    </template>
                </div>
            </div>

            {{-- Variable mappings --}}
            <template x-if="variables.length > 0">
                <div class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
                    <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Variable Mappings</p>
                    </div>
                    <div class="px-4 py-3 bg-white dark:bg-gray-950 space-y-3">
                        <template x-for="variable in variables" :key="variable">
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
                                    <template x-for="opt in options" :key="opt">
                                        <option :value="opt" x-text="opt"></option>
                                    </template>
                                </select>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Preview --}}
            <template x-if="body || phone">
                <div class="space-y-3">
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
            </template>
        </div>
    </div>
</x-dynamic-component>
