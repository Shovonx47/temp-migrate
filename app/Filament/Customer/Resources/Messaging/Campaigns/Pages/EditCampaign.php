<?php

namespace App\Filament\Customer\Resources\Messaging\Campaigns\Pages;

use App\Filament\Customer\Resources\Messaging\Campaigns\CampaignResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
