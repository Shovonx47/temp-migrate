<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="recipientUpload(@js($getState() ?? []))"
        x-on:recipient-upload-changed.window="
            $wire.set('{{ $getStatePath() }}', $event.detail)
        "
        class="space-y-4"
    >
        {{-- Drop zone --}}
        <div
            x-show="!fileName"
            x-on:dragover="handleDragOver($event)"
            x-on:dragleave="handleDragLeave($event)"
            x-on:drop="handleDrop($event)"
            :class="{
                'border-primary-500 bg-primary-50 dark:bg-primary-950': isDragging,
                'border-gray-300 dark:border-gray-700': !isDragging,
            }"
            class="relative flex flex-col items-center justify-center px-6 py-10 border-2 border-dashed rounded-xl transition-colors cursor-pointer hover:border-primary-400 dark:hover:border-primary-600"
        >
            <input
                type="file"
                accept=".csv,.txt,.xlsx,.xls"
                x-on:change="handleFileInput($event)"
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
            />

            <div class="flex flex-col items-center text-center pointer-events-none">
                <x-filament::icon
                    icon="heroicon-o-cloud-arrow-up"
                    class="h-10 w-10 text-gray-400 dark:text-gray-500 mb-3"
                />
                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                    Drop file here or <span class="text-primary-600 dark:text-primary-400">browse</span>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    CSV, XLSX, or XLS &middot; Max 10MB
                </p>
            </div>
        </div>

        {{-- Processing state --}}
        <div
            x-show="isProcessing"
            x-cloak
            class="flex items-center gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-900 rounded-lg"
        >
            <svg class="animate-spin h-4 w-4 text-primary-600" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span class="text-sm text-gray-700 dark:text-gray-200">Parsing file...</span>
        </div>

        {{-- Error --}}
        <div
            x-show="error"
            x-cloak
            class="flex items-start gap-2 px-4 py-3 bg-danger-50 dark:bg-danger-950 border border-danger-200 dark:border-danger-900 rounded-lg"
        >
            <x-filament::icon
                icon="heroicon-o-exclamation-triangle"
                class="h-5 w-5 text-danger-600 dark:text-danger-400 flex-shrink-0 mt-0.5"
            />
            <p class="text-sm text-danger-700 dark:text-danger-300" x-text="error"></p>
        </div>

        {{-- File loaded state --}}
        <div
            x-show="fileName && !isProcessing"
            x-cloak
            class="space-y-4"
        >
            {{-- File summary --}}
            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
                <div class="flex items-center gap-3 min-w-0">
                    <x-filament::icon
                        icon="heroicon-o-document-text"
                        class="h-5 w-5 text-gray-500 dark:text-gray-400 flex-shrink-0"
                    />
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" x-text="fileName"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <span x-text="rows.length"></span> rows &middot;
                            <span x-text="headers.length"></span> columns &middot;
                            <span x-text="hasHeaderRow ? 'Header detected' : 'No header'"></span>
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    x-on:click="reset()"
                    class="text-xs font-medium text-danger-600 dark:text-danger-400 hover:text-danger-700 dark:hover:text-danger-300"
                >
                    Remove
                </button>
            </div>

            {{-- Preview table --}}
            <div
                x-show="previewRows.length > 0"
                class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden"
            >
                <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
                        Preview (first <span x-text="previewRows.length"></span> rows)
                    </p>
                </div>

                <div class="overflow-x-auto" x-show="headers.length > 0">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <template x-for="(header, idx) in headers" :key="idx">
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    <span x-text="header"></span>
                                </th>
                            </template>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-950">
                        <template x-for="(row, rowIdx) in previewRows" :key="rowIdx">
                            <tr>
                                <template x-for="(cell, cellIdx) in row" :key="cellIdx">
                                    <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                        <span x-text="cell"></span>
                                    </td>
                                </template>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>

                <div
                    x-show="rows.length > previewRows.length"
                    class="px-4 py-2 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800"
                >
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        + <span x-text="rows.length - previewRows.length"></span> more rows
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
