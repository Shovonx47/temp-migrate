<?php

namespace App\Filament\Admin\Resources\Messaging\DeliveryReports\Pages;

use App\Filament\Admin\Resources\Messaging\DeliveryReports\DeliveryReportResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDeliveryReport extends ViewRecord
{
    protected static string $resource = DeliveryReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
