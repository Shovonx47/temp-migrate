<?php

namespace App\Filament\Customer\Resources\Messaging\Campaigns;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Customer\Resources\Messaging\Campaigns\Pages\CreateCampaign;
use App\Filament\Customer\Resources\Messaging\Campaigns\Pages\ListCampaigns;
use App\Filament\Customer\Resources\Messaging\Campaigns\Pages\ViewCampaign;
use App\Filament\Customer\Resources\Messaging\Campaigns\RelationManagers\RecipientsRelationManager;
use App\Filament\Customer\Resources\Messaging\Campaigns\Schemas\CampaignForm;
use App\Filament\Customer\Resources\Messaging\Campaigns\Schemas\CampaignInfolist;
use App\Filament\Customer\Resources\Messaging\Campaigns\Tables\CampaignsTable;
use App\Models\Campaign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Messaging;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $shouldRegisterNavigation = false; // TODO:: paused for now

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
            RecipientsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaigns::route('/'),
            'create' => CreateCampaign::route('/create'),
            'view' => ViewCampaign::route('/{record}'),
        ];
    }
}
