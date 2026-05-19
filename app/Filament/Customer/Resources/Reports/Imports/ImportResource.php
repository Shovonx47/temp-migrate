<?php

namespace App\Filament\Customer\Resources\Reports\Imports;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Customer\Resources\Reports\Imports\Pages\ListImports;
use App\Filament\Customer\Resources\Reports\Imports\Tables\ImportsTable;
use App\Models\Import;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ImportResource extends Resource
{
    protected static ?string $model = Import::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowUp;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Reports;

    public static function table(Table $table): Table
    {
        return ImportsTable::configure($table);
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
            'index' => ListImports::route('/'),
        ];
    }
}
