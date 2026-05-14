<?php

namespace App\Filament\Admin\Resources\Templates;

use App\Filament\Admin\Resources\Templates\Pages\CreateTemplate;
use App\Filament\Admin\Resources\Templates\Pages\EditTemplate;
use App\Filament\Admin\Resources\Templates\Pages\ListTemplates;
use App\Filament\Admin\Resources\Templates\Pages\ViewTemplate;
use App\Filament\Admin\Resources\Templates\Schemas\TemplateForm;
use App\Filament\Admin\Resources\Templates\Schemas\TemplateInfolist;
use App\Filament\Admin\Resources\Templates\Tables\TemplatesTable;
use App\Models\Template;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Messaging';

    public static function form(Schema $schema): Schema
    {
        return TemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TemplatesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TemplateInfolist::configure($schema);
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
            'index' => ListTemplates::route('/'),
            'view' => ViewTemplate::route('/{record}'),
            'create' => CreateTemplate::route('/create'),
            'edit' => EditTemplate::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
