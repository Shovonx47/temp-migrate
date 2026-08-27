<?php

namespace App\Filament\Admin\Resources\ApprovalCenter\IpWhitelists\Pages;

use App\Filament\Admin\Resources\ApprovalCenter\IpWhitelists\IpWhitelistRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListIpWhitelistRequests extends ListRecords
{
    protected static string $resource = IpWhitelistRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
