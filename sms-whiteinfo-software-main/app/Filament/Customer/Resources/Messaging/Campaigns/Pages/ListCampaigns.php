<?php

namespace App\Filament\Customer\Resources\Messaging\Campaigns\Pages;

use App\Filament\Customer\Resources\Messaging\Campaigns\CampaignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCampaigns extends ListRecords
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
