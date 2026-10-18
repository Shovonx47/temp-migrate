<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Settings\SignupRegisterGateways\Pages;

use App\Filament\Admin\Resources\Settings\SignupRegisterGateways\SignupRegisterGatewayResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSignupRegisterGateway extends EditRecord
{
    protected static string $resource = SignupRegisterGatewayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
