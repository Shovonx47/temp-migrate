<?php

namespace App\Filament\Admin\Resources\Messaging\Messages;

use App\Enums\AdminNavigationGroup;
use App\Filament\Admin\Resources\Messaging\Messages\Pages\CreateMessage;
use App\Filament\Admin\Resources\Messaging\Messages\Pages\EditMessage;
use App\Filament\Admin\Resources\Messaging\Messages\Pages\ListMessages;
use App\Filament\Admin\Resources\Messaging\Messages\Pages\ViewMessage;
use App\Filament\Admin\Resources\Messaging\Messages\Schemas\MessageForm;
use App\Filament\Admin\Resources\Messaging\Messages\Schemas\MessageInfolist;
use App\Filament\Admin\Resources\Messaging\Messages\Tables\MessagesTable;
use App\Models\Message;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftEllipsis;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Messaging;

    public static function form(Schema $schema): Schema
    {
        return MessageForm::configure($schema);
    }

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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessages::route('/'),
            'create' => CreateMessage::route('/create'),
            'view' => ViewMessage::route('/{record}'),
            'edit' => EditMessage::route('/{record}/edit'),
        ];
    }
}
