<?php

namespace App\Filament\Customer\Resources\Messaging\Templates\Pages;

use App\Filament\Customer\Resources\Messaging\Templates\TemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTemplate extends CreateRecord
{
    protected static string $resource = TemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
