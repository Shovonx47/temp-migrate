<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Roles\Pages;

use App\Filament\Admin\Resources\Roles\RoleResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();
        $data['scope'] = 'superman';
        $data['created_by'] = $user?->id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
