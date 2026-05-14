<?php

namespace App\Filament\Admin\Resources\Groups;

use App\Filament\Admin\Resources\Groups\Pages\CreateGroup;
use App\Filament\Admin\Resources\Groups\Pages\EditGroup;
use App\Filament\Admin\Resources\Groups\Pages\ListGroups;
use App\Filament\Admin\Resources\Groups\Pages\ViewGroup;
use App\Filament\Admin\Resources\Groups\Schemas\GroupForm;
use App\Filament\Admin\Resources\Groups\Schemas\GroupInfolist;
use App\Filament\Admin\Resources\Groups\Tables\GroupsTable;
use App\Models\Group;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Audience';

    public static function form(Schema $schema): Schema
    {
        return GroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GroupsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GroupInfolist::configure($schema);
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
            'index' => ListGroups::route('/'),
            'view' => ViewGroup::route('/{record}'),
            'create' => CreateGroup::route('/create'),
            'edit' => EditGroup::route('/{record}/edit'),
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
