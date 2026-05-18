<?php

namespace App\Filament\Admin\Resources\Settings\Operators;

use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Resources\Settings\Operators\Pages\CreateOperator;
use App\Filament\Admin\Resources\Settings\Operators\Pages\EditOperator;
use App\Filament\Admin\Resources\Settings\Operators\Pages\ListOperators;
use App\Filament\Admin\Resources\Settings\Operators\Pages\ViewOperator;
use App\Filament\Admin\Resources\Settings\Operators\Schemas\OperatorForm;
use App\Filament\Admin\Resources\Settings\Operators\Schemas\OperatorInfolist;
use App\Filament\Admin\Resources\Settings\Operators\Tables\OperatorsTable;
use App\Models\Operator;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OperatorResource extends Resource
{
    protected static ?string $model = Operator::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Settings;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return OperatorForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OperatorInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OperatorsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOperators::route('/'),
            'create' => CreateOperator::route('/create'),
            'view' => ViewOperator::route('/{record}'),
            'edit' => EditOperator::route('/{record}/edit'),
        ];
    }
}
