<?php

namespace App\Filament\Customer\Resources\Messaging\Campaigns\Pages;

use App\Filament\Customer\Resources\Messaging\Campaigns\CampaignResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCampaign extends ViewRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
