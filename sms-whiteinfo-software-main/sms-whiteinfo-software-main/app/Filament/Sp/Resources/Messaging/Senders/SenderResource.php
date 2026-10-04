<?php

namespace App\Filament\Sp\Resources\Messaging\Senders;

use App\Enums\SpNavigationGroup;
use App\Filament\Sp\Resources\Messaging\Senders\Pages\CreateSender;
use App\Filament\Sp\Resources\Messaging\Senders\Pages\ListSenders;
use App\Filament\Sp\Resources\Messaging\Senders\Pages\ViewSender;
use App\Filament\Sp\Resources\Messaging\Senders\RelationManagers\CustomersRelationManager;
use App\Filament\Sp\Resources\Messaging\Senders\Schemas\SenderForm;
use App\Filament\Sp\Resources\Messaging\Senders\Schemas\SenderInfolist;
use App\Filament\Sp\Resources\Messaging\Senders\Tables\SendersTable;
use App\Models\Sender;
use App\Services\SpPermissionService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SenderResource extends Resource
{
    protected static ?string $model = Sender::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = SpNavigationGroup::Messaging;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'sender_id';

    protected static ?string $navigationLabel = 'Senders';

    protected static ?string $modelLabel = 'Sender';

    protected static ?string $pluralModelLabel = 'Senders';

    public static function form(Schema $schema): Schema
    {
        return SenderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SendersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SenderInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            CustomersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSenders::route('/'),
            'create' => CreateSender::route('/create'),
            'view' => ViewSender::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function (Builder $query): void {
                $query->where('requested_by', auth()->id())
                    ->orWhereHas('users', fn (Builder $users) => $users->where('users.id', auth()->id()));
            });
    }

    public static function canViewAny(): bool
    {
        return app(SpPermissionService::class)->can(auth('sp')->user(), 'messaging.view');
    }

    public static function canCreate(): bool
    {
        return app(SpPermissionService::class)->can(auth('sp')->user(), 'messaging.manage');
    }
}
