<?php

namespace App\Filament\Admin\Resources\Gateways\Pages;

use App\Filament\Admin\Resources\Gateways\GatewayResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGateway extends CreateRecord
{
    protected static string $resource = GatewayResource::class;
}
