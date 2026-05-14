<?php

namespace App\Filament\Admin\Resources\Settings\Gateways\Pages;

use App\Filament\Admin\Resources\Settings\Gateways\GatewayResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewGateway extends ViewRecord
{
    protected static string $resource = GatewayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
