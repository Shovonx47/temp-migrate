<?php

namespace App\Filament\Customer\Resources\Messaging\Senders\Pages;

use App\Filament\Customer\Resources\Messaging\Senders\SenderResource;
use Filament\Resources\Pages\ListRecords;

class ListSenders extends ListRecords
{
    protected static string $resource = SenderResource::class;
}
