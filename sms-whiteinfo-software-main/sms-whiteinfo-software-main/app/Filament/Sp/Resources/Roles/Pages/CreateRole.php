<?php

declare(strict_types=1);

namespace App\Filament\Sp\Resources\Roles\Pages;

use App\Filament\Sp\Resources\Roles\RoleResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();
        $data['scope'] = (string) ($user?->spOwnerId() ?? $user?->id);
        $data['created_by'] = $user?->id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
