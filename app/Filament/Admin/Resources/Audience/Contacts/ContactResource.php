<?php

namespace App\Filament\Admin\Resources\Audience\Contacts;

use App\Enums\AdminNavigationGroup;
use App\Filament\Admin\Resources\Audience\Contacts\Pages\CreateContact;
use App\Filament\Admin\Resources\Audience\Contacts\Pages\EditContact;
use App\Filament\Admin\Resources\Audience\Contacts\Pages\ListContacts;
use App\Filament\Admin\Resources\Audience\Contacts\Pages\ViewContact;
use App\Filament\Admin\Resources\Audience\Contacts\Schemas\ContactForm;
use App\Filament\Admin\Resources\Audience\Contacts\Schemas\ContactInfolist;
use App\Filament\Admin\Resources\Audience\Contacts\Tables\ContactsTable;
use App\Models\Contact;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Audience;

    public static function form(Schema $schema): Schema
    {
        return ContactForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContactInfolist::configure($schema);
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
            'index' => ListContacts::route('/'),
            'create' => CreateContact::route('/create'),
            'view' => ViewContact::route('/{record}'),
            'edit' => EditContact::route('/{record}/edit'),
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
