<?php

namespace App\Filament\Customer\Resources\Audience\Contacts\Pages;

use App\Filament\Customer\Resources\Audience\Contacts\ContactResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
