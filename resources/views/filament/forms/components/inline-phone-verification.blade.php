@php
    $statePath = $getStatePath();
    $phoneVerified = $this->phone_verified ?? false;
    $phoneOtpSent = $this->phone_otp_sent ?? false;
    $phoneSuccess = $this->phone_success ?? null;
    $phoneError = $this->phone_error ?? null;
    $hasFieldError = $errors->has($statePath);
@endphp

<div id="field-wrap-phone" class="space-y-2" x-data="{
    cooldown: @entangle('phone_cooldown').live,
    timer: null,
    startTimer() {
        if (this.cooldown > 0 && !this.timer) {
            this.timer = setInterval(() => {
                if (this.cooldown > 0) {
                    this.cooldown--;
                } else {
                    clearInterval(this.timer);
                    this.timer = null;
                }
            }, 1000);
        }
    }
}" x-init="startTimer(); $watch('cooldown', () => startTimer())">
    <label for="data.phone" class="flex items-center gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
        {{ $getLabel() }}
        @if ($isRequired())
            <span class="text-red-500">*</span>
        @endif
    </label>

    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                <x-filament::icon icon="heroicon-o-phone" class="w-5 h-5 text-gray-400 dark:text-gray-500" />
            </div>
                        <input
                type="tel"
                wire:model.blur="data.phone"
                id="data.phone"
                placeholder="+8801XXXXXXXXX"
                required
                @disabled($phoneVerified)
                class="block w-full pl-10 pr-3 py-2 text-sm rounded-lg border {{ $hasFieldError ? 'border-red-400 dark:border-red-700 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-700 focus:ring-teal-500 focus:border-teal-500' }} bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 disabled:opacity-60 disabled:bg-gray-50 dark:disabled:bg-gray-800"
            />
        </div>

        <div>
            @if ($phoneVerified)
                <div class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-teal-50 dark:bg-teal-950/40 border border-teal-200 dark:border-teal-800 text-teal-700 dark:text-teal-400 text-xs font-semibold whitespace-nowrap shadow-xs">
                    <x-filament::icon icon="heroicon-m-check-badge" class="w-4 h-4 text-teal-600 dark:text-teal-400" />
                    <span>Verified</span>
                </div>
            @else
                <button
                    type="button"
                    wire:click="sendPhoneOtp"
                    wire:loading.attr="disabled"
                    :disabled="cooldown > 0"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-medium rounded-lg text-white bg-teal-600 hover:bg-teal-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-xs cursor-pointer"
                >
                    <span wire:loading.remove wire:target="sendPhoneOtp" x-text="cooldown > 0 ? `Resend (${cooldown}s)` : 'Send SMS OTP'"></span>
                    <span wire:loading wire:target="sendPhoneOtp">Sending...</span>
                </button>
            @endif
        </div>
    </div>

    @error($statePath)
        <div class="p-2.5 rounded-lg bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 text-xs text-red-700 dark:text-red-300 flex items-center gap-2">
            <x-filament::icon icon="heroicon-m-exclamation-triangle" class="w-4 h-4 text-red-600 dark:text-red-400 shrink-0" />
            <span>{{ $message }}</span>
        </div>
    @enderror

    @if ($phoneSuccess)
        <div class="p-2.5 rounded-lg bg-teal-50 dark:bg-teal-950/30 border border-teal-200 dark:border-teal-900 text-xs text-teal-700 dark:text-teal-300 flex items-center gap-2">
            <x-filament::icon icon="heroicon-m-check-circle" class="w-4 h-4 text-teal-600 dark:text-teal-400 shrink-0" />
            <span>{{ $phoneSuccess }}</span>
        </div>
    @endif

    @if ($phoneError)
        <div class="p-2.5 rounded-lg bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 text-xs text-red-700 dark:text-red-300 flex items-center gap-2">
            <x-filament::icon icon="heroicon-m-exclamation-triangle" class="w-4 h-4 text-red-600 dark:text-red-400 shrink-0" />
            <span>{{ $phoneError }}</span>
        </div>
    @endif

    @if (!$phoneVerified && $phoneOtpSent)
        <div class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-200 dark:border-gray-700 animate-fadeIn">
            <div class="relative flex-1">
                <input
                    type="text"
                    wire:model="phone_otp"
                    maxlength="6"
                    placeholder="Enter 6-digit SMS OTP"
                    class="block w-full px-3 py-1.5 text-sm font-mono tracking-wider rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                />
            </div>
            <button
                type="button"
                wire:click="verifyPhoneOtp"
                wire:loading.attr="disabled"
                class="px-4 py-1.5 text-xs font-semibold rounded-lg text-white bg-teal-600 hover:bg-teal-700 focus:outline-hidden focus:ring-2 focus:ring-teal-500 transition shadow-xs cursor-pointer"
            >
                <span wire:loading.remove wire:target="verifyPhoneOtp">Verify</span>
                <span wire:loading wire:target="verifyPhoneOtp">Verifying...</span>
            </button>
        </div>
    @endif
</div>