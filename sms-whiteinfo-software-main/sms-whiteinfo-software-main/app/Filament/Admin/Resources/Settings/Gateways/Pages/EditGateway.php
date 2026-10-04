<?php

namespace App\Filament\Admin\Resources\Settings\Gateways\Pages;

use App\Filament\Admin\Resources\Settings\Gateways\GatewayResource;
use App\Models\Gateway;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

/**
 * @property Gateway $record
 */
class EditGateway extends EditRecord
{
    protected static string $resource = GatewayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['credentials'] = $this->record->credentials;
        $data['config'] = $this->record->config;

        return parent::mutateFormDataBeforeFill($data);
    }
}
