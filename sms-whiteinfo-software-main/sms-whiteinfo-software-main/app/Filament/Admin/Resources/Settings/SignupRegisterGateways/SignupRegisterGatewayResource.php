<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Settings\SignupRegisterGateways;

use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Resources\Settings\SignupRegisterGateways\Pages\CreateSignupRegisterGateway;
use App\Filament\Admin\Resources\Settings\SignupRegisterGateways\Pages\EditSignupRegisterGateway;
use App\Filament\Admin\Resources\Settings\SignupRegisterGateways\Pages\ListSignupRegisterGateways;
use App\Filament\Admin\Resources\Settings\SignupRegisterGateways\Schemas\SignupRegisterGatewayForm;
use App\Filament\Admin\Resources\Settings\SignupRegisterGateways\Tables\SignupRegisterGatewaysTable;
use App\Models\SignupRegisterGateway;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SignupRegisterGatewayResource extends Resource
{
    protected static ?string $model = SignupRegisterGateway::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Settings;

    protected static ?string $navigationLabel = 'Signup Register Gateways';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return SignupRegisterGatewayForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SignupRegisterGatewaysTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSignupRegisterGateways::route('/'),
            'create' => CreateSignupRegisterGateway::route('/create'),
            'edit' => EditSignupRegisterGateway::route('/{record}/edit'),
        ];
    }
}
