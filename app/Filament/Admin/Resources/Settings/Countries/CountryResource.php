<?php

namespace App\Filament\Admin\Resources\Settings\Countries;

use App\Enums\AdminNavigationGroup;
use App\Filament\Admin\Resources\Settings\Countries\Pages\CreateCountry;
use App\Filament\Admin\Resources\Settings\Countries\Pages\EditCountry;
use App\Filament\Admin\Resources\Settings\Countries\Pages\ListCountries;
use App\Filament\Admin\Resources\Settings\Countries\Pages\ViewCountry;
use App\Filament\Admin\Resources\Settings\Countries\Schemas\CountryForm;
use App\Filament\Admin\Resources\Settings\Countries\Schemas\CountryInfolist;
use App\Filament\Admin\Resources\Settings\Countries\Tables\CountriesTable;
use App\Models\Country;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Settings;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CountryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CountryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CountriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCountries::route('/'),
            'create' => CreateCountry::route('/create'),
            'view' => ViewCountry::route('/{record}'),
            'edit' => EditCountry::route('/{record}/edit'),
        ];
    }
}
