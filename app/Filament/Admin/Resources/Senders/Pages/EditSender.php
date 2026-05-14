<?php

namespace App\Filament\Admin\Resources\Senders\Pages;

use App\Filament\Admin\Resources\Senders\SenderResource;
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
}
