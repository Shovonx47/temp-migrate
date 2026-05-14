<?php

namespace App\Filament\Admin\Resources\Gateways\Pages;

use App\Filament\Admin\Resources\Gateways\GatewayResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGateways extends ListRecords
{
    protected static string $resource = GatewayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
