<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Settings\EmailGateways;

use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Resources\Settings\EmailGateways\Pages\CreateEmailGateway;
use App\Filament\Admin\Resources\Settings\EmailGateways\Pages\EditEmailGateway;
use App\Filament\Admin\Resources\Settings\EmailGateways\Pages\ListEmailGateways;
use App\Filament\Admin\Resources\Settings\EmailGateways\Schemas\EmailGatewayForm;
use App\Filament\Admin\Resources\Settings\EmailGateways\Tables\EmailGatewaysTable;
use App\Models\EmailGateway;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EmailGatewayResource extends Resource
{
    protected static ?string $model = EmailGateway::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Settings;

    protected static ?string $navigationLabel = 'Email (SMTP) Gateways';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return EmailGatewayForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmailGatewaysTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailGateways::route('/'),
            'create' => CreateEmailGateway::route('/create'),
            'edit' => EditEmailGateway::route('/{record}/edit'),
        ];
    }
}
