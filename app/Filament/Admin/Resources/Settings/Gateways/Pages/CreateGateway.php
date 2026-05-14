<?php

namespace App\Filament\Admin\Resources\Settings\Gateways\Pages;

use App\Filament\Admin\Resources\Settings\Gateways\GatewayResource;
use App\Models\Gateway;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateGateway extends CreateRecord
{
    protected static string $resource = GatewayResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return Gateway::create($data);
    }
}
