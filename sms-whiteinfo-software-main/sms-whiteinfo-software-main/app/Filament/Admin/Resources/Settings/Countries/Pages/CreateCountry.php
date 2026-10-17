<?php

namespace App\Filament\Admin\Resources\Settings\Countries\Pages;

use App\Filament\Admin\Resources\Settings\Countries\CountryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCountry extends CreateRecord
{
    protected static string $resource = CountryResource::class;
}
