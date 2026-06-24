<?php

namespace App\Filament\Customer\Resources\Messaging\Templates;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Customer\Resources\Messaging\Templates\Pages\CreateTemplate;
use App\Filament\Customer\Resources\Messaging\Templates\Pages\EditTemplate;
use App\Filament\Customer\Resources\Messaging\Templates\Pages\ListTemplates;
use App\Filament\Customer\Resources\Messaging\Templates\Pages\ViewTemplate;
use App\Filament\Customer\Resources\Messaging\Templates\RelationManagers\CampaignsRelationManager;
use App\Filament\Customer\Resources\Messaging\Templates\Schemas\TemplateForm;
use App\Filament\Customer\Resources\Messaging\Templates\Schemas\TemplateInfolist;
use App\Filament\Customer\Resources\Messaging\Templates\Tables\TemplatesTable;
use App\Models\Template;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Messaging;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

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
            CampaignsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTemplates::route('/'),
            'create' => CreateTemplate::route('/create'),
            'view' => ViewTemplate::route('/{record}'),
            'edit' => EditTemplate::route('/{record}/edit'),
        ];
    }
}
