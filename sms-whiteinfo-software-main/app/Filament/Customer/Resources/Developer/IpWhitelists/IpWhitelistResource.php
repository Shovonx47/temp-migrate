<?php

namespace App\Filament\Customer\Resources\Developer\IpWhitelists;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Customer\Resources\Developer\IpWhitelists\Pages\ListIpWhitelists;
use App\Filament\Customer\Resources\Developer\IpWhitelists\Schemas\IpWhitelistForm;
use App\Filament\Customer\Resources\Developer\IpWhitelists\Tables\IpWhitelistsTable;
use App\Models\IpWhitelistRequest;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class IpWhitelistResource extends Resource
{
    protected static ?string $model = IpWhitelistRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Developer;

    protected static ?string $navigationLabel = 'IP Whitelist';

    protected static ?string $modelLabel = 'IP Whitelist Request';

    protected static ?string $pluralModelLabel = 'IP Whitelist Requests';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) Filament::auth()->user()?->api_access;
    }

    public static function canViewAny(): bool
    {
        return (bool) Filament::auth()->user()?->api_access;
    }

    public static function form(Schema $schema): Schema
    {
        return IpWhitelistForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IpWhitelistsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $userId = Filament::auth()->id();

        return parent::getEloquentQuery()
            ->where('user_id', $userId);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIpWhitelists::route('/'),
        ];
    }
}
