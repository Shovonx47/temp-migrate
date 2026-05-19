<?php

namespace App\Filament\Customer\Resources\Developer\AccessTokens;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Customer\Resources\Developer\AccessTokens\Pages\ListAccessTokens;
use App\Filament\Customer\Resources\Developer\AccessTokens\Schemas\AccessTokenForm;
use App\Filament\Customer\Resources\Developer\AccessTokens\Tables\AccessTokensTable;
use App\Models\AccessToken;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AccessTokenResource extends Resource
{
    protected static ?string $model = AccessToken::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Developer;

    public static function form(Schema $schema): Schema
    {
        return AccessTokenForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccessTokensTable::configure($table);
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
            'index' => ListAccessTokens::route('/'),
        ];
    }
}
