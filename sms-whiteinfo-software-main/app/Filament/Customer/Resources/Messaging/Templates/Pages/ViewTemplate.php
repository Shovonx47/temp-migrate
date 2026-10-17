<?php

namespace App\Filament\Customer\Resources\Messaging\Templates\Pages;

use App\Filament\Customer\Resources\Messaging\Templates\TemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTemplate extends ViewRecord
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
