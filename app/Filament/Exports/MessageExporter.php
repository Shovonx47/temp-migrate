<?php

namespace App\Filament\Exports;

use App\Models\Message;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class MessageExporter extends Exporter
{
    protected static ?string $model = Message::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('user.name'),
            ExportColumn::make('campaign.name'),
            ExportColumn::make('sender.id'),
            ExportColumn::make('gateway_id'),
            ExportColumn::make('to'),
            ExportColumn::make('body'),
            ExportColumn::make('status'),
            ExportColumn::make('gateway_message_id'),
            ExportColumn::make('cost'),
            ExportColumn::make('failed_reason'),
            ExportColumn::make('sent_at'),
            ExportColumn::make('failed_at'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your message export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
