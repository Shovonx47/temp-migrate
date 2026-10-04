<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Customer header summary --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $this->record->name }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $this->record->email }} &bull; {{ $this->record->phone }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">User ID: {{ $this->record->id }} &bull; Registered: {{ $this->record->created_at->format('d M Y') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                    @if($this->record->kyc_status?->value === 'verified') bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300
                    @elseif($this->record->kyc_status?->value === 'under_review') bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300
                    @elseif($this->record->kyc_status?->value === 'rejected') bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300
                    @elseif($this->record->kyc_status?->value === 'resubmit_required') bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300
                    @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 @endif">
                    KYC: {{ $this->record->kyc_status?->getLabel() ?? 'Pending' }}
                </span>
            </div>
        </div>

        {{-- Current document preview --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 uppercase tracking-wide">Current Documents on File</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">NID Front</p>
                    @if($this->record->front_nid)
                        <span class="text-xs text-green-600 dark:text-green-400 font-medium">✓ Uploaded</span>
                    @else
                        <span class="text-xs text-red-500">✗ Not uploaded</span>
                    @endif
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">NID Back</p>
                    @if($this->record->back_nid)
                        <span class="text-xs text-green-600 dark:text-green-400 font-medium">✓ Uploaded</span>
                    @else
                        <span class="text-xs text-red-500">✗ Not uploaded</span>
                    @endif
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Live Photo</p>
                    @if($this->record->live_photo)
                        <span class="text-xs text-green-600 dark:text-green-400 font-medium">✓ Uploaded</span>
                    @else
                        <span class="text-xs text-red-500">✗ Not uploaded</span>
                    @endif
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">NID Number</p>
                    @if($this->record->nid_number)
                        <span class="text-xs text-green-600 dark:text-green-400 font-medium">{{ $this->record->nid_number }}</span>
                    @else
                        <span class="text-xs text-red-500">✗ Not provided</span>
                    @endif
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Trade License</p>
                    @if($this->record->trade_license_submitted)
                        <span class="text-xs text-green-600 dark:text-green-400 font-medium">✓ Submitted</span>
                    @else
                        <span class="text-xs text-gray-400">— Not submitted</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Editable sections --}}
        {{ $this->nidForm }}
        {{ $this->livePhotoForm }}
        {{ $this->tradeLicForm }}
        {{ $this->verifyForm }}

    </div>
</x-filament-panels::page>
