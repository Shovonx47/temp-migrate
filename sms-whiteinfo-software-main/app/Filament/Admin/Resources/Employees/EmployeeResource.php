<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Employees;

use App\Enums\SupermanNavigationGroup;
use App\Enums\UserType;
use App\Filament\Admin\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Admin\Resources\Employees\Pages\EditEmployee;
use App\Filament\Admin\Resources\Employees\Pages\ListEmployees;
use App\Filament\Admin\Resources\Employees\Schemas\EmployeeForm;
use App\Filament\Admin\Resources\Employees\Tables\EmployeesTable;
use App\Models\User;
use App\Services\SpPermissionService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class EmployeeResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Employees;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Staff & Employees';

    protected static ?string $modelLabel = 'Staff';

    protected static ?string $pluralModelLabel = 'Staff';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return EmployeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'edit' => EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('type', UserType::Superman);
    }

    public static function canViewAny(): bool
    {
        return app(SpPermissionService::class)->can(auth('superman')->user(), 'employees.view');
    }

    public static function canCreate(): bool
    {
        return app(SpPermissionService::class)->can(auth('superman')->user(), 'employees.create');
    }

    public static function canEdit(Model $record): bool
    {
        return app(SpPermissionService::class)->can(auth('superman')->user(), 'employees.edit');
    }

    public static function canDelete(Model $record): bool
    {
        return app(SpPermissionService::class)->can(auth('superman')->user(), 'employees.delete');
    }
}
