<?php

namespace App\Filament\Admin\Resources\Messaging\Campaigns;

use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Resources\Messaging\Campaigns\Pages\CreateCampaign;
use App\Filament\Admin\Resources\Messaging\Campaigns\Pages\EditCampaign;
use App\Filament\Admin\Resources\Messaging\Campaigns\Pages\ListCampaigns;
use App\Filament\Admin\Resources\Messaging\Campaigns\Pages\ViewCampaign;
use App\Filament\Admin\Resources\Messaging\Campaigns\Schemas\CampaignForm;
use App\Filament\Admin\Resources\Messaging\Campaigns\Schemas\CampaignInfolist;
use App\Filament\Admin\Resources\Messaging\Campaigns\Tables\CampaignsTable;
use App\Models\Campaign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Messaging;

    public static function form(Schema $schema): Schema
    {
        return CampaignForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CampaignsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CampaignInfolist::configure($schema);
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
            'index' => ListCampaigns::route('/'),
            'create' => CreateCampaign::route('/create'),
            'view' => ViewCampaign::route('/{record}'),
            'edit' => EditCampaign::route('/{record}/edit'),
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
