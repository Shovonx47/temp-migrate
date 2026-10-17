<?php

namespace App\Filament\Exports;

use App\Models\DeliveryReport;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class DeliveryReportExporter extends Exporter
{
    protected static ?string $model = DeliveryReport::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('gateway_id'),
            ExportColumn::make('message_id'),
            ExportColumn::make('gateway_message_id'),
            ExportColumn::make('status'),
            ExportColumn::make('raw_payload'),
            ExportColumn::make('received_at'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your delivery report export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
