<?php

namespace App\Filament\Admin\Resources\Messages\Pages;

use App\Filament\Admin\Resources\Messages\MessageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMessage extends ViewRecord
{
    protected static string $resource = MessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
