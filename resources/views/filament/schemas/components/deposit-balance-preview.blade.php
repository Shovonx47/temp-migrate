@php
    /** @var \App\Models\User|null $customer */
    $customer = $customer ?? (isset($getRecord) ? $getRecord() : null) ?? (isset($get) && $get('customer_id') ? \App\Models\User::find($get('customer_id')) : null);
    
    $user = auth()->user();
    $isSp = ($user?->type?->isSp() || (auth('sp')->check()));
    $spUser = $isSp ? (($user?->type?->isSp() && $user?->sp_id) ? \App\Models\User::find($user->sp_id) : $user) : null;
    $spBalance = (float) ($spUser?->balance ?? 0);
@endphp

@if ($customer)
    <div
        x-data="{
            currentBalance: {{ (float) ($customer->balance ?? 0) }},
            amount: 0,
            isSp: {{ $isSp ? 'true' : 'false' }},
            spBalance: {{ (float) $spBalance }},

            get newBalance() {
                const add = parseFloat(this.amount) || 0;
                return this.currentBalance + add;
            },
            get spRemaining() {
                const add = parseFloat(this.amount) || 0;
                return this.spBalance - add;
            },
            get isInsufficientSp() {
                if (!this.isSp) return false;
                const add = parseFloat(this.amount) || 0;
                return add > 0 && add > this.spBalance;
            },
            get isInsufficientCustomer() {
                const add = parseFloat(this.amount) || 0;
                return add < 0 && Math.abs(add) > this.currentBalance;
            },
            formatMoney(val) {
                const num = parseFloat(val) || 0;
                return 'BDT ' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            init() {
                const input = document.querySelector('[data-deposit-amount-input]');
                if (input && input.value) {
                    this.amount = parseFloat(input.value) || 0;
                }
            }
        }"
        @input.window="if ($event.target.matches('[data-deposit-amount-input]')) { amount = parseFloat($event.target.value) || 0; }"
        @change.window="if ($event.target.matches('[data-deposit-amount-input]')) { amount = parseFloat($event.target.value) || 0; }"
        class="space-y-1.5"
    >
        <div 
            class="flex items-center justify-between rounded-lg border px-3 py-2 text-xs transition-colors"
            :class="amount < 0 
                ? 'border-amber-200/80 bg-amber-50/70 text-amber-900 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-200' 
                : 'border-emerald-200/80 bg-emerald-50/70 text-emerald-900 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-200'"
        >
            <span 
                class="font-semibold"
                x-text="amount < 0 ? 'Updated Balance (After Return):' : 'Upgraded Balance:'"
            >
                Upgraded Balance:
            </span>
            <span
                class="font-mono font-bold text-sm"
                :class="amount < 0 ? 'text-amber-700 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'"
                x-text="formatMoney(newBalance)"
            >
                BDT {{ number_format((float) $customer->balance, 2) }}
            </span>
        </div>

        <template x-if="isInsufficientCustomer">
            <p class="text-[11px] font-medium text-red-600 dark:text-red-400 px-1">
                ⚠️ Return amount exceeds customer's current balance (BDT {{ number_format((float) $customer->balance, 2) }}).
            </p>
        </template>

        @if ($isSp && $spUser)
            <div
                class="flex items-center justify-between px-3 py-1.5 rounded-lg border text-[11px]"
                :class="isInsufficientSp 
                    ? 'border-red-300 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-400' 
                    : (amount < 0 
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-400'
                        : 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-800 dark:bg-gray-900/50 dark:text-gray-400')"
            >
                <span x-text="amount < 0 ? 'SP Wallet (Refunded):' : 'SP Wallet Remaining:'">SP Wallet Remaining:</span>
                <span class="font-mono font-semibold" x-text="formatMoney(spRemaining)">
                    BDT {{ number_format((float) $spBalance, 2) }}
                </span>
            </div>
            <template x-if="isInsufficientSp">
                <p class="text-[11px] font-medium text-red-600 dark:text-red-400 px-1">
                    ⚠️ Amount exceeds your SP balance (BDT {{ number_format((float) $spBalance, 2) }}).
                </p>
            </template>
        @endif
    </div>
@endif
