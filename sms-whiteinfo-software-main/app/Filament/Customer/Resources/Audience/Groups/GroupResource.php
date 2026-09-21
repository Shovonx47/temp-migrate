<?php

namespace App\Filament\Customer\Resources\Audience\Groups;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Customer\Resources\Audience\Groups\Pages\CreateGroup;
use App\Filament\Customer\Resources\Audience\Groups\Pages\EditGroup;
use App\Filament\Customer\Resources\Audience\Groups\Pages\ListGroups;
use App\Filament\Customer\Resources\Audience\Groups\Pages\ViewGroup;
use App\Filament\Customer\Resources\Audience\Groups\RelationManagers\ContactsRelationManager;
use App\Filament\Customer\Resources\Audience\Groups\Schemas\GroupForm;
use App\Filament\Customer\Resources\Audience\Groups\Schemas\GroupInfolist;
use App\Filament\Customer\Resources\Audience\Groups\Tables\GroupsTable;
use App\Models\Group;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Audience;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

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
            ContactsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGroups::route('/'),
            'create' => CreateGroup::route('/create'),
            'view' => ViewGroup::route('/{record}'),
            'edit' => EditGroup::route('/{record}/edit'),
        ];
    }
}
