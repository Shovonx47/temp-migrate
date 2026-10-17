<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Settings\SignupRegisterGateways\Pages;

use App\Filament\Admin\Resources\Settings\SignupRegisterGateways\SignupRegisterGatewayResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSignupRegisterGateway extends CreateRecord
{
    protected static string $resource = SignupRegisterGatewayResource::class;
}
