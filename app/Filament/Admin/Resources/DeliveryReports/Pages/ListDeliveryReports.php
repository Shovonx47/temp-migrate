<?php

namespace App\Filament\Admin\Resources\DeliveryReports\Pages;

use App\Filament\Admin\Resources\DeliveryReports\DeliveryReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeliveryReports extends ListRecords
{
    protected static string $resource = DeliveryReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
