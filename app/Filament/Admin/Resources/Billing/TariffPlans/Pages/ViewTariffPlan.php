<?php

namespace App\Filament\Admin\Resources\Billing\TariffPlans\Pages;

use App\Filament\Admin\Resources\Billing\TariffPlans\TariffPlanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTariffPlan extends ViewRecord
{
    protected static string $resource = TariffPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
