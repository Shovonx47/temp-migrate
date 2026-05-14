<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\Pages;

use App\Filament\Customer\Resources\Messaging\Messages\MessageResource;
use Filament\Resources\Pages\ListRecords;

class ListMessages extends ListRecords
{
    protected static string $resource = MessageResource::class;
}
