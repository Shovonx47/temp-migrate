<?php

namespace App\Filament\Admin\Resources\Users\Customers\Pages;

use App\Enums\UserType;
use App\Filament\Admin\Resources\Users\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = UserType::Customer;

        return $data;
    }
}
