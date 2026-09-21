<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Settings\SignupRegisterGateways\Pages;

use App\Filament\Admin\Resources\Settings\SignupRegisterGateways\SignupRegisterGatewayResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSignupRegisterGateways extends ListRecords
{
    protected static string $resource = SignupRegisterGatewayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
