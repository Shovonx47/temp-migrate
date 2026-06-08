<?php

namespace App\Filament\Admin\Resources\Messaging\Batches;

use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Resources\Messaging\Batches\Pages\ListBatches;
use App\Filament\Admin\Resources\Messaging\Batches\Pages\ViewBatch;
use App\Filament\Admin\Resources\Messaging\Batches\RelationManagers\MessagesRelationManager;
use App\Filament\Admin\Resources\Messaging\Batches\Schemas\BatchInfolist;
use App\Filament\Admin\Resources\Messaging\Batches\Tables\BatchesTable;
use App\Models\Batch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BatchResource extends Resource
{
    protected static ?string $model = Batch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Messaging;

    protected static ?string $navigationLabel = 'Batches';

    protected static ?string $modelLabel = 'Batch';

    protected static ?string $pluralModelLabel = 'Batches';

    public static function infolist(Schema $schema): Schema
    {
        return BatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BatchesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBatches::route('/'),
            'view' => ViewBatch::route('/{record}'),
        ];
    }
}
