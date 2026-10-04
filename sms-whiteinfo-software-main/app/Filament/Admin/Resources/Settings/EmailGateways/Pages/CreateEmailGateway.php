<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Settings\EmailGateways\Pages;

use App\Filament\Admin\Resources\Settings\EmailGateways\EmailGatewayResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmailGateway extends CreateRecord
{
    protected static string $resource = EmailGatewayResource::class;
}
