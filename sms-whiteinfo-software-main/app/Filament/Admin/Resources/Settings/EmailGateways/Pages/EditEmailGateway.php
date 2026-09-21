<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Settings\EmailGateways\Pages;

use App\Filament\Admin\Resources\Settings\EmailGateways\EmailGatewayResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmailGateway extends EditRecord
{
    protected static string $resource = EmailGatewayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
