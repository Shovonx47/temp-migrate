<?php

namespace App\Observers;

use App\Models\GatewayDippingCharge;
use App\Models\GatewayDippingChargeHistory;
use Illuminate\Support\Facades\Auth;

class GatewayDippingChargeObserver
{
    public function created(GatewayDippingCharge $charge): void
    {
        $this->log($charge, 'created', null, null, null, null, null, null, null, null);
    }

    public function updated(GatewayDippingCharge $charge): void
    {
        $this->log(
            charge: $charge,
            event: 'updated',
            oldMasking: $charge->getOriginal('masking_rate'),
            newMasking: $charge->masking_rate,
            oldNonMasking: $charge->getOriginal('non_masking_rate'),
            newNonMasking: $charge->non_masking_rate,
            oldRateMode: $charge->getOriginal('rate_mode'),
            newRateMode: $charge->rate_mode?->value ?? $charge->rate_mode,
            oldEffectiveFrom: $charge->getOriginal('effective_from'),
            newEffectiveFrom: $charge->effective_from,
        );
    }

    public function deleted(GatewayDippingCharge $charge): void
    {
        $this->log(
            charge: $charge,
            event: 'deleted',
            oldMasking: $charge->masking_rate,
            newMasking: null,
            oldNonMasking: $charge->non_masking_rate,
            newNonMasking: null,
            oldRateMode: $charge->rate_mode?->value,
            newRateMode: null,
            oldEffectiveFrom: $charge->effective_from,
            newEffectiveFrom: null,
        );
    }

    private function log(
        GatewayDippingCharge $charge,
        string $event,
        ?float $oldMasking,
        ?float $newMasking,
        ?float $oldNonMasking,
        ?float $newNonMasking,
        mixed $oldRateMode,
        mixed $newRateMode,
        mixed $oldEffectiveFrom,
        mixed $newEffectiveFrom,
    ): void {
        GatewayDippingChargeHistory::create([
            'gateway_dipping_charge_id' => $charge->id,
            'gateway_id' => $charge->gateway_id,
            'operator_id' => $charge->operator_id,
            'old_masking_rate' => $oldMasking,
            'new_masking_rate' => $newMasking,
            'old_non_masking_rate' => $oldNonMasking,
            'new_non_masking_rate' => $newNonMasking,
            'old_rate_mode' => is_string($oldRateMode) ? $oldRateMode : ($oldRateMode?->value ?? null),
            'new_rate_mode' => is_string($newRateMode) ? $newRateMode : ($newRateMode?->value ?? null),
            'old_effective_from' => $oldEffectiveFrom,
            'new_effective_from' => $newEffectiveFrom,
            'event' => $event,
            'changed_by' => Auth::id(),
            'changed_at' => now(),
        ]);
    }
}
