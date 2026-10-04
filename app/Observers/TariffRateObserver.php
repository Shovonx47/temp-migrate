<?php

namespace App\Observers;

use App\Models\TariffRate;
use App\Models\TariffRateHistory;
use Illuminate\Support\Facades\Auth;

class TariffRateObserver
{
    public function created(TariffRate $rate): void
    {
        $this->log($rate, 'created', null, null, null, null);
    }

    public function updated(TariffRate $rate): void
    {
        $this->log(
            rate: $rate,
            event: 'updated',
            oldMasking: $rate->getOriginal('masking_price'),
            newMasking: $rate->masking_price,
            oldNonMasking: $rate->getOriginal('non_masking_price'),
            newNonMasking: $rate->non_masking_price,
        );
    }

    public function deleted(TariffRate $rate): void
    {
        $this->log($rate, 'deleted', $rate->masking_price, null, $rate->non_masking_price, null);
    }

    private function log(
        TariffRate $rate,
        string $event,
        ?float $oldMasking,
        ?float $newMasking,
        ?float $oldNonMasking,
        ?float $newNonMasking,
    ): void {
        TariffRateHistory::create([
            'tariff_plan_id' => $rate->tariff_plan_id,
            'tariff_rate_id' => $rate->id,
            'operator_id' => $rate->operator_id,
            'old_masking_price' => $oldMasking,
            'new_masking_price' => $newMasking,
            'old_non_masking_price' => $oldNonMasking,
            'new_non_masking_price' => $newNonMasking,
            'event' => $event,
            'changed_by' => Auth::id(),
            'changed_at' => now(),
        ]);
    }
}
