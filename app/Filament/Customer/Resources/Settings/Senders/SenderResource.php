<?php

namespace App\Filament\Customer\Resources\Settings\Senders;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Customer\Resources\Settings\Senders\Pages\ListSenders;
use App\Filament\Customer\Resources\Settings\Senders\Pages\ViewSender;
use App\Filament\Customer\Resources\Settings\Senders\Schemas\SenderInfolist;
use App\Filament\Customer\Resources\Settings\Senders\Tables\SendersTable;
use App\Models\Sender;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SenderResource extends Resource
{
    protected static ?string $model = Sender::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Settings;

    public static function table(Table $table): Table
    {
        return SendersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SenderInfolist::configure($schema);
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
            'index' => ListSenders::route('/'),
            'view' => ViewSender::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('users', fn ($query) => $query->where('user_id', auth()->id()));
    }
}
