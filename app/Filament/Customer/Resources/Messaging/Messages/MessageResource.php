<?php

namespace App\Filament\Customer\Resources\Messaging\Messages;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Customer\Resources\Messaging\Messages\Pages\CreateQuickMessage;
use App\Filament\Customer\Resources\Messaging\Messages\Pages\CreateSmartMessage;
use App\Filament\Customer\Resources\Messaging\Messages\Pages\ListMessages;
use App\Filament\Customer\Resources\Messaging\Messages\Pages\ViewMessage;
use App\Filament\Customer\Resources\Messaging\Messages\Schemas\MessageForm;
use App\Filament\Customer\Resources\Messaging\Messages\Schemas\MessageInfolist;
use App\Filament\Customer\Resources\Messaging\Messages\Tables\MessagesTable;
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

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Billing;

    protected static ?string $navigationLabel = 'Sent Message Details';

    protected static ?string $modelLabel = 'Sent Message Detail';

    protected static ?string $pluralModelLabel = 'Sent Message Details';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return MessagesTable::configure($table);
    }

    public static function form(Schema $schema): Schema
    {
        return MessageForm::configure($schema);
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
            'create-smart' => CreateSmartMessage::route('/smart'),
            'create-quick' => CreateQuickMessage::route('/quick'),
            'view' => ViewMessage::route('/{record}'),
        ];
    }
}
