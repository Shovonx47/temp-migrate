<?php

namespace App\Filament\Sp\Resources\Billing\TariffPlans\Pages;

use App\Filament\Sp\Resources\Billing\TariffPlans\TariffPlanResource;
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
}
