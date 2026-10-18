<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Roles;

use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Resources\Roles\Pages\CreateRole;
use App\Filament\Admin\Resources\Roles\Pages\EditRole;
use App\Filament\Admin\Resources\Roles\Pages\ListRoles;
use App\Filament\Admin\Resources\Roles\Schemas\RoleForm;
use App\Filament\Admin\Resources\Roles\Tables\RolesTable;
use App\Models\SpRole;
use App\Services\SpPermissionService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = SpRole::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::RolesPermissions;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Roles';

    protected static ?string $modelLabel = 'Role';

    protected static ?string $pluralModelLabel = 'Roles';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('scope', 'superman');
    }

    public static function canViewAny(): bool
    {
        return app(SpPermissionService::class)->can(auth('superman')->user(), 'roles.view');
    }

    public static function canCreate(): bool
    {
        return app(SpPermissionService::class)->can(auth('superman')->user(), 'roles.manage');
    }

    public static function canEdit(Model $record): bool
    {
        return app(SpPermissionService::class)->can(auth('superman')->user(), 'roles.manage');
    }

    public static function canDelete(Model $record): bool
    {
        return app(SpPermissionService::class)->can(auth('superman')->user(), 'roles.manage');
    }
}
