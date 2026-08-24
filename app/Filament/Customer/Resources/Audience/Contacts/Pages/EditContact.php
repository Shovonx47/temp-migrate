<?php

namespace App\Filament\Customer\Resources\Audience\Contacts\Pages;

use App\Filament\Customer\Resources\Audience\Contacts\ContactResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditContact extends EditRecord
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['phone'])) {
            $cleaned = preg_replace('/[^\d+]/', '', (string) $data['phone']);
            if (preg_match('/^(?:\+?88)?0?(1[3-9]\d{8})$/', $cleaned, $m)) {
                $data['phone'] = '+880'.$m[1];
            }
        }

        return $data;
    }
}
