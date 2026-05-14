<?php

namespace App\Filament\Admin\Resources\DeliveryReports\Pages;

use App\Filament\Admin\Resources\DeliveryReports\DeliveryReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryReport extends EditRecord
{
    protected static string $resource = DeliveryReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
