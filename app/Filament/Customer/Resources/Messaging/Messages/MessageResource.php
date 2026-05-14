<?php

namespace App\Filament\Customer\Resources\Messaging\Messages;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Customer\Resources\Messaging\Messages\Pages\ListMessages;
use App\Filament\Customer\Resources\Messaging\Messages\Pages\ViewMessage;
use App\Filament\Customer\Resources\Messaging\Messages\RelationManagers\DeliveryReportsRelationManager;
use App\Filament\Customer\Resources\Messaging\Messages\Schemas\MessageInfolist;
use App\Filament\Customer\Resources\Messaging\Messages\Tables\MessagesTable;
use App\Models\Message;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftEllipsis;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Messaging;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'uid';

    public static function table(Table $table): Table
    {
        return MessagesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MessageInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            DeliveryReportsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessages::route('/'),
            'view' => ViewMessage::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }
}
