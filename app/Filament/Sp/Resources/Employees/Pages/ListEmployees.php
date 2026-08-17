<?php

namespace App\Filament\Sp\Resources\Employees\Pages;

use App\Filament\Sp\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\ListRecords;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;
}
