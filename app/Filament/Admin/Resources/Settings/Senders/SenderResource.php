<?php

namespace App\Filament\Admin\Resources\Settings\Senders;

use App\Enums\AdminNavigationGroup;
use App\Filament\Admin\Resources\Settings\Senders\Pages\CreateSender;
use App\Filament\Admin\Resources\Settings\Senders\Pages\EditSender;
use App\Filament\Admin\Resources\Settings\Senders\Pages\ListSenders;
use App\Filament\Admin\Resources\Settings\Senders\Pages\ViewSender;
use App\Filament\Admin\Resources\Settings\Senders\Schemas\SenderForm;
use App\Filament\Admin\Resources\Settings\Senders\Schemas\SenderInfolist;
use App\Filament\Admin\Resources\Settings\Senders\Tables\SendersTable;
use App\Models\Sender;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SenderResource extends Resource
{
    protected static ?string $model = Sender::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Settings;

    public static function form(Schema $schema): Schema
    {
        return SenderForm::configure($schema);
    }

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
            'create' => CreateSender::route('/create'),
            'view' => ViewSender::route('/{record}'),
            'edit' => EditSender::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
