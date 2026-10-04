<?php

namespace App\Filament\Admin\Resources\Messaging\Messages\Pages;

use App\Filament\Admin\Resources\Messaging\Messages\MessageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMessage extends EditRecord
{
    protected static string $resource = MessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
