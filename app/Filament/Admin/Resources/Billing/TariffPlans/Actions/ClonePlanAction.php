<?php

namespace App\Filament\Admin\Resources\Billing\TariffPlans\Actions;

use App\Models\TariffPlan;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class ClonePlanAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'clone';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Clone')
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Clone this plan?')
            ->modalDescription('A full copy of this plan and all its rate rows will be created as a Draft. You can then rename and edit it before activating.')
            ->action(function (TariffPlan $record): void {
                $newPlan = $record->replicate([
                    'created_by',
                    'slug',
                ]);
                $newPlan->name = $record->name.' (Copy)';
                $newPlan->status = 'draft';
                $newPlan->created_by = auth()->id();
                $newPlan->save();

                // Deep-copy all rate rows
                foreach ($record->tariffRates as $rate) {
                    $newRate = $rate->replicate();
                    $newRate->tariff_plan_id = $newPlan->id;
                    $newRate->saveQuietly(); // skip observer for the clone (no meaningful "rate change")
                }
            })
            ->successNotificationTitle('Plan cloned successfully.');
    }
}
