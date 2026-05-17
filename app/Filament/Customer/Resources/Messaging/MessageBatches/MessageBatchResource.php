<?php

namespace App\Filament\Customer\Resources\Messaging\MessageBatches;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Customer\Resources\Messaging\MessageBatches\Pages\ListMessageBatches;
use App\Filament\Customer\Resources\Messaging\MessageBatches\Pages\ViewMessageBatch;
use App\Filament\Customer\Resources\Messaging\MessageBatches\RelationManagers\MessagesRelationManager;
use App\Filament\Customer\Resources\Messaging\MessageBatches\Schemas\MessageBatchInfolist;
use App\Filament\Customer\Resources\Messaging\MessageBatches\Tables\MessageBatchesTable;
use App\Models\MessageBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MessageBatchResource extends Resource
{
    protected static ?string $model = MessageBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Messaging;

    protected static ?string $navigationLabel = 'Batches';

    protected static ?string $modelLabel = 'Batch';

    protected static ?string $pluralModelLabel = 'Batches';

    public static function infolist(Schema $schema): Schema
    {
        return MessageBatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MessageBatchesTable::configure($table);
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
            'index' => ListMessageBatches::route('/'),
            'view' => ViewMessageBatch::route('/{record}'),
        ];
    }
}
