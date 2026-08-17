<?php

namespace App\Filament\Sp\Resources\Employees\Pages;

use App\Enums\UserType;
use App\Filament\Sp\Resources\Employees\EmployeeResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = UserType::Sp;
        $data['sp_id'] = Filament::auth()->id();
        $data['balance'] = 0;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
