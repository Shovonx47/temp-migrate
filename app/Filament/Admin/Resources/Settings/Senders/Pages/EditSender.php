<?php

namespace App\Filament\Admin\Resources\Settings\Senders\Pages;

use App\Enums\SenderType;
use App\Filament\Admin\Resources\Settings\Senders\SenderResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditSender extends EditRecord
{
    protected static string $resource = SenderResource::class;

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
        if (isset($data['type'])) {
            $type = $data['type'] instanceof SenderType
                ? $data['type']
                : SenderType::tryFrom((string) $data['type']);

            if ($type !== null) {
                $data['type'] = $type->maskingCategory();
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['type'])) {
            $data['type'] = SenderType::normalizeStoredValue($data['type']);
        }

        return $data;
    }
}
