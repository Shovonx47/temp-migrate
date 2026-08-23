<?php

namespace App\Filament\Customer\Resources\Messaging\Batches;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Customer\Resources\Messaging\Batches\Pages\ListBatches;
use App\Filament\Customer\Resources\Messaging\Batches\Pages\ViewBatch;
use App\Filament\Customer\Resources\Messaging\Batches\RelationManagers\MessagesRelationManager;
use App\Filament\Customer\Resources\Messaging\Batches\Schemas\BatchInfolist;
use App\Filament\Customer\Resources\Messaging\Batches\Tables\BatchesTable;
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

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Messaging;

    protected static ?string $navigationLabel = 'Sent Message Logs';

    protected static ?string $modelLabel = 'Sent Message Log';

    protected static ?string $pluralModelLabel = 'Sent Message Logs';

    protected static ?int $navigationSort = 1;

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
