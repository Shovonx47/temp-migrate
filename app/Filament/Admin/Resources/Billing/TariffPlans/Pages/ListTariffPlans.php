<?php

namespace App\Filament\Admin\Resources\Billing\TariffPlans\Pages;

use App\Filament\Admin\Resources\Billing\TariffPlans\TariffPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTariffPlans extends ListRecords
{
    protected static string $resource = TariffPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Tariff Plan'),
        ];
    }
}
