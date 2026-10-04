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

        if (! empty($data['phone'])) {
            $cleaned = preg_replace('/[^\d+]/', '', (string) $data['phone']);
            if (preg_match('/^(?:\+?88)?0?(1[3-9]\d{8})$/', $cleaned, $m)) {
                $data['phone'] = '+880'.$m[1];
            }
        }

        return $data;
    }
}
