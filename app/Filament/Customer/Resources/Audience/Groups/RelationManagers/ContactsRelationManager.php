<?php

namespace App\Filament\Customer\Resources\Audience\Groups\RelationManagers;

use App\Filament\Customer\Resources\Audience\Contacts\ContactResource;
use Filament\Resources\RelationManagers\RelationManager;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $relatedResource = ContactResource::class;
}
