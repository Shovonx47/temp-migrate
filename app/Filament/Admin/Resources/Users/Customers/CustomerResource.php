<?php

namespace App\Filament\Admin\Resources\Users\Customers;

use App\Enums\AdminNavigationGroup;
use App\Filament\Admin\Resources\Users\Customers\Pages\CreateCustomer;
use App\Filament\Admin\Resources\Users\Customers\Pages\EditCustomer;
use App\Filament\Admin\Resources\Users\Customers\Pages\ListCustomers;
use App\Filament\Admin\Resources\Users\Customers\Pages\ViewCustomer;
use App\Filament\Admin\Resources\Users\Customers\Schemas\CustomerForm;
use App\Filament\Admin\Resources\Users\Customers\Schemas\CustomerInfolist;
use App\Filament\Admin\Resources\Users\Customers\Tables\CustomersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::User;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $modelLabel = 'Customer';

    protected static ?string $pluralModelLabel = 'Customers';

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerInfolist::configure($schema);
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
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'view' => ViewCustomer::route('/{record}'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->scopes(['customers']);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
