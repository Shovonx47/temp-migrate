<?php

namespace App\Filament\Customer\Resources\Reports\Exports;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Customer\Resources\Reports\Exports\Pages\ListExports;
use App\Filament\Customer\Resources\Reports\Exports\Tables\ExportsTable;
use App\Models\Export;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ExportResource extends Resource
{
    protected static ?string $model = Export::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowDown;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Reports;

    public static function table(Table $table): Table
    {
        return ExportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExports::route('/'),
        ];
    }
}
