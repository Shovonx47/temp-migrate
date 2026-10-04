<div class="space-y-6">
    {{-- Header Banner --}}
    <div class="rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 p-6 text-white shadow-lg">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-white/20 px-3 py-1 text-xs font-semibold text-white backdrop-blur-md">
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-4 w-4" />
                    Regulatory Compliance Framework
                </div>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-white">Sender & CLI Policy Standards</h1>
                <p class="mt-1 text-sm text-emerald-100 max-w-2xl">
                    Official technical specifications, BTRC regulatory rules, character encodings, and compliance constraints governing Masking and Non-Masking Sender IDs.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white backdrop-blur-md ring-1 ring-white/20">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-300 animate-pulse"></span>
                    Policy Engine Active
                </span>
            </div>
        </div>
    </div>

    {{-- Interactive Policy Compliance Validator Tool --}}
    <div class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex items-center gap-3 border-b border-gray-200 pb-4 dark:border-gray-800">
            <div class="rounded-lg bg-emerald-500/10 p-2 text-emerald-600 dark:text-emerald-400">
                <x-filament::icon icon="heroicon-o-sparkles" class="h-5 w-5" />
            </div>
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Interactive Sender Policy & Encoding Validator</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Test any proposed Sender ID or message payload in real time against telecom regulatory compliance filters.</p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Input Controls --}}
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sender Category</label>
                    <div class="mt-1 grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            wire:click="$set('testSenderType', 'masking')"
                            class="flex items-center justify-center gap-2 rounded-lg py-2 text-xs font-semibold transition {{ $testSenderType === 'masking' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}"
                        >
                            <x-filament::icon icon="heroicon-o-identification" class="h-4 w-4" />
                            Masking (Brand Alphanumeric)
                        </button>
                        <button
                            type="button"
                            wire:click="$set('testSenderType', 'numeric')"
                            class="flex items-center justify-center gap-2 rounded-lg py-2 text-xs font-semibold transition {{ $testSenderType === 'numeric' ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}"
                        >
                            <x-filament::icon icon="heroicon-o-phone" class="h-4 w-4" />
                            Non-Masking (Numeric CLI)
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Test Sender ID / CLI</label>
                    <input
                        type="text"
                        wire:model.live.debounce.200ms="testSenderId"
                        placeholder="{{ $testSenderType === 'masking' ? 'e.g. WHITEINFO or MYBRAND' : 'e.g. 01712345678 or 10666' }}"
                        class="mt-1 block w-full rounded-lg border-gray-300 bg-white py-2 font-mono text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sample Message Content</label>
                        <span class="text-xs font-mono text-gray-400">
                            {{ $validationResults['charCount'] ?? 0 }} chars | {{ $validationResults['partCount'] ?? 1 }} part(s)
                        </span>
                    </div>
                    <textarea
                        wire:model.live.debounce.200ms="testMessageBody"
                        rows="3"
                        placeholder="Type message text here to test GSM-7 vs Unicode encoding and part calculation..."
                        class="mt-1 block w-full rounded-lg border-gray-300 bg-white py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    ></textarea>
                </div>
            </div>

            {{-- Validation Results Display --}}
            <div class="rounded-xl bg-gray-50 p-5 ring-1 ring-gray-200 dark:bg-gray-800/50 dark:ring-gray-800 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Compliance Verdict</span>
                        @if (!($validationResults['hasInput'] ?? false))
                            <span class="inline-flex items-center rounded-md bg-gray-200 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                Awaiting Input
                            </span>
                        @elseif ($validationResults['status'] === 'compliant')
                            <span class="inline-flex items-center gap-1.5 rounded-md bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4 text-emerald-600" />
                                Fully Compliant
                            </span>
                        @elseif ($validationResults['status'] === 'warning')
                            <span class="inline-flex items-center gap-1.5 rounded-md bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-4 w-4 text-amber-600" />
                                Requires Attention
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-md bg-red-100 px-2.5 py-1 text-xs font-bold text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                <x-filament::icon icon="heroicon-o-x-circle" class="h-4 w-4 text-red-600" />
                                Non-Compliant
                            </span>
                        @endif
                    </div>

                    <div class="mt-4 space-y-2">
                        @if (!($validationResults['hasInput'] ?? false))
                            <p class="text-xs text-gray-500">Enter a sender ID and optional message text on the left to verify BTRC rules.</p>
                        @endif

                        @foreach ($validationResults['errors'] ?? [] as $err)
                            <div class="flex items-start gap-2 rounded-lg bg-red-50 p-2.5 text-xs text-red-700 dark:bg-red-950/30 dark:text-red-300">
                                <x-filament::icon icon="heroicon-o-x-circle" class="h-4 w-4 flex-shrink-0 text-red-500 mt-0.5" />
                                <span>{{ $err }}</span>
                            </div>
                        @endforeach

                        @foreach ($validationResults['warnings'] ?? [] as $warn)
                            <div class="flex items-start gap-2 rounded-lg bg-amber-50 p-2.5 text-xs text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">
                                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-4 w-4 flex-shrink-0 text-amber-500 mt-0.5" />
                                <span>{{ $warn }}</span>
                            </div>
                        @endforeach

                        @foreach ($validationResults['passes'] ?? [] as $pass)
                            <div class="flex items-start gap-2 rounded-lg bg-emerald-50 p-2.5 text-xs text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                                <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4 flex-shrink-0 text-emerald-500 mt-0.5" />
                                <span>{{ $pass }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-gray-200 text-xs text-gray-400 dark:border-gray-700 flex items-center justify-between">
                    <span>Encoding: <strong class="text-gray-700 dark:text-gray-300">{{ ($validationResults['isUnicode'] ?? false) ? 'UTF-16 (Unicode)' : 'GSM-7 (Standard)' }}</strong></span>
                    <span>Billing Parts: <strong class="text-gray-700 dark:text-gray-300">{{ $validationResults['partCount'] ?? 1 }}</strong></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Official Policy Documentation Grid --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        {{-- Policy Card 1: Masking Regulations --}}
        <div class="flex flex-col justify-between rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div>
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-emerald-500/10 p-2 text-emerald-500">
                        <x-filament::icon icon="heroicon-o-identification" class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-gray-900 dark:text-white">Masking Sender ID Rules</h4>
                </div>
                <ul class="mt-4 space-y-2 text-xs text-gray-600 dark:text-gray-400">
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-500 font-bold">•</span>
                        <span><strong>Length:</strong> 3 to 11 alphanumeric characters max.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-500 font-bold">•</span>
                        <span><strong>Character Set:</strong> English alphabets (A-Z, a-z), digits (0-9), and spaces only.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-500 font-bold">•</span>
                        <span><strong>KYC Requirement:</strong> Trade license, BTRC NOC or official NOC letter for brand verification.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-500 font-bold">•</span>
                        <span><strong>Restrictions:</strong> No financial brand spoofing (e.g. bKash, Nagad) without direct MFS authority.</span>
                    </li>
                </ul>
            </div>
            <div class="mt-4 rounded-lg bg-gray-50 p-2.5 text-[11px] text-gray-500 dark:bg-gray-800/60">
                Enforced by: <strong>BTRC Directive 2020/SMS-Reg</strong>
            </div>
        </div>

        {{-- Policy Card 2: Non-Masking Regulations --}}
        <div class="flex flex-col justify-between rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div>
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-blue-500/10 p-2 text-blue-500">
                        <x-filament::icon icon="heroicon-o-phone" class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-gray-900 dark:text-white">Non-Masking Numeric CLI Policy</h4>
                </div>
                <ul class="mt-4 space-y-2 text-xs text-gray-600 dark:text-gray-400">
                    <li class="flex items-start gap-2">
                        <span class="text-blue-500 font-bold">•</span>
                        <span><strong>Format:</strong> 11-digit MSISDN starting with standard prefix (e.g. 017, 018, 019, 015, 013, 014).</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-blue-500 font-bold">•</span>
                        <span><strong>Shortcodes:</strong> 4-6 digit dedicated shortcodes allowed upon gateway carrier approval.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-blue-500 font-bold">•</span>
                        <span><strong>Dynamic CLI:</strong> Dynamic sender rotating pools must be pre-authorized under an active Gateway.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-blue-500 font-bold">•</span>
                        <span><strong>Billing:</strong> Non-masking tariff rates apply per segment.</span>
                    </li>
                </ul>
            </div>
            <div class="mt-4 rounded-lg bg-gray-50 p-2.5 text-[11px] text-gray-500 dark:bg-gray-800/60">
                Enforced by: <strong>MNO Direct Interconnect Policy</strong>
            </div>
        </div>

        {{-- Policy Card 3: Multi-part & Encoding Policy --}}
        <div class="flex flex-col justify-between rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div>
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-purple-500/10 p-2 text-purple-500">
                        <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-gray-900 dark:text-white">Character Count & Unicode Standard</h4>
                </div>
                <ul class="mt-4 space-y-2 text-xs text-gray-600 dark:text-gray-400">
                    <li class="flex items-start gap-2">
                        <span class="text-purple-500 font-bold">•</span>
                        <span><strong>GSM-7 (English):</strong> Part 1 = 160 chars. Multipart = 153 chars/part (UDH overhead: 7 bytes).</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-purple-500 font-bold">•</span>
                        <span><strong>Unicode (Bengali/UTF-16):</strong> Part 1 = 70 chars. Multipart = 67 chars/part.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-purple-500 font-bold">•</span>
                        <span><strong>Emoji Policy:</strong> Emojis force message into UTF-16 (Unicode) 70 chars limit.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-purple-500 font-bold">•</span>
                        <span><strong>Max Multipart:</strong> Up to 10 parts maximum per single SMS transaction.</span>
                    </li>
                </ul>
            </div>
            <div class="mt-4 rounded-lg bg-gray-50 p-2.5 text-[11px] text-gray-500 dark:bg-gray-800/60">
                Standard: <strong>3GPP TS 23.038 / SMPP v3.4</strong>
            </div>
        </div>
    </div>
</div>
