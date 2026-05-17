<?php

namespace App\Filament\Exports;

use App\Models\Campaign;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class CampaignExporter extends Exporter
{
    protected static ?string $model = Campaign::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('user.name'),
            ExportColumn::make('group.name'),
            ExportColumn::make('sender.id'),
            ExportColumn::make('name'),
            ExportColumn::make('body'),
            ExportColumn::make('status'),
            ExportColumn::make('total_recipients'),
            ExportColumn::make('total_sent'),
            ExportColumn::make('total_failed'),
            ExportColumn::make('scheduled_at'),
            ExportColumn::make('started_at'),
            ExportColumn::make('completed_at'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
            ExportColumn::make('deleted_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your campaign export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
