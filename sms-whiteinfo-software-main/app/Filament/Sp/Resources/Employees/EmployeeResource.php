<?php

namespace App\Filament\Sp\Resources\Employees;

use App\Enums\SpNavigationGroup;
use App\Filament\Sp\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Sp\Resources\Employees\Pages\EditEmployee;
use App\Filament\Sp\Resources\Employees\Pages\ListEmployees;
use App\Filament\Sp\Resources\Employees\Pages\ViewEmployee;
use App\Filament\Sp\Resources\Employees\Schemas\EmployeeForm;
use App\Filament\Sp\Resources\Employees\Schemas\EmployeeInfolist;
use App\Filament\Sp\Resources\Employees\Tables\EmployeesTable;
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

class EmployeeResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = SpNavigationGroup::Employees;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Employees';

    protected static ?string $modelLabel = 'Employee';

    protected static ?string $pluralModelLabel = 'Employees';

    public static function form(Schema $schema): Schema
    {
        return EmployeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmployeeInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'view' => ViewEmployee::route('/{record}'),
            'edit' => EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('type', 'sp')
            ->whereNotNull('sp_id');
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
        return app(SpPermissionService::class)->can(auth('sp')->user(), 'employees.view');
    }

    public static function canCreate(): bool
    {
        return app(SpPermissionService::class)->can(auth('sp')->user(), 'employees.create');
    }

    public static function canEdit(Model $record): bool
    {
        return app(SpPermissionService::class)->can(auth('sp')->user(), 'employees.edit');
    }

    public static function canDelete(Model $record): bool
    {
        return app(SpPermissionService::class)->can(auth('sp')->user(), 'employees.delete');
    }
}
