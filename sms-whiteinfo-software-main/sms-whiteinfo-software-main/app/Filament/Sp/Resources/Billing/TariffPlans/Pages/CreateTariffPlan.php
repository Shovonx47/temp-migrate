<?php

namespace App\Filament\Sp\Resources\Billing\TariffPlans\Pages;

use App\Filament\Sp\Resources\Billing\TariffPlans\TariffPlanResource;
use App\Models\Operator;
use App\Models\TariffRate;
use Filament\Resources\Pages\CreateRecord;

class CreateTariffPlan extends CreateRecord
{
    protected static string $resource = TariffPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $data['sp_id'] = ($user?->type?->isSp() && $user?->sp_id) ? $user->sp_id : $user?->id;
        $data['created_by'] = $user?->id;

        return $data;
    }

    protected function afterCreate(): void
    {
        $operators = Operator::where('is_active', true)->get();
        foreach ($operators as $operator) {
            TariffRate::firstOrCreate(
                [
                    'tariff_plan_id' => $this->record->id,
                    'operator_id' => $operator->id,
                ],
                [
                    'non_masking_price' => $operator->non_masking_price,
                    'masking_price' => $operator->masking_price,
                ]
            );
        }
    }
}
