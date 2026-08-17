<?php

namespace App\Filament\Sp\Resources\Billing\TariffPlans\Pages;

use App\Filament\Sp\Resources\Billing\TariffPlans\TariffPlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTariffPlan extends CreateRecord
{
    protected static string $resource = TariffPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sp_id'] = auth()->id();
        $data['created_by'] = auth()->id();

        return $data;
    }
}
