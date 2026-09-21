<?php

namespace App\Filament\Sp\Resources\Customers;

use App\Enums\SpNavigationGroup;
use App\Filament\Sp\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Sp\Resources\Customers\Pages\EditCustomer;
use App\Filament\Sp\Resources\Customers\Pages\ListCustomers;
use App\Filament\Sp\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Sp\Resources\Customers\Schemas\CustomerForm;
use App\Filament\Sp\Resources\Customers\Schemas\CustomerInfolist;
use App\Filament\Sp\Resources\Customers\Tables\CustomersTable;
use App\Models\User;
use App\Services\SpPermissionService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = SpNavigationGroup::Customers;

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
        return [];
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
        return parent::getEloquentQuery()
            ->where('type', 'customer');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canViewAny(): bool
    {
        return app(SpPermissionService::class)->can(auth('sp')->user(), 'customers.view');
    }

    public static function canCreate(): bool
    {
        return app(SpPermissionService::class)->can(auth('sp')->user(), 'customers.create');
    }

    public static function canEdit(Model $record): bool
    {
        return app(SpPermissionService::class)->can(auth('sp')->user(), 'customers.edit');
    }

    public static function canDelete(Model $record): bool
    {
        return app(SpPermissionService::class)->can(auth('sp')->user(), 'customers.delete');
    }
}
