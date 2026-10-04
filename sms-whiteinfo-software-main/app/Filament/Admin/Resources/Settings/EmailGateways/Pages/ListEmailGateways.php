<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Settings\EmailGateways\Pages;

use App\Filament\Admin\Resources\Settings\EmailGateways\EmailGatewayResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmailGateways extends ListRecords
{
    protected static string $resource = EmailGatewayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
