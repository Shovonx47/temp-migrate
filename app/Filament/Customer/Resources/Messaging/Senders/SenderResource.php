<?php

namespace App\Filament\Customer\Resources\Messaging\Senders;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Customer\Resources\Messaging\Senders\Pages\ListSenders;
use App\Filament\Customer\Resources\Messaging\Senders\Tables\SendersTable;
use App\Models\Sender;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SenderResource extends Resource
{
    protected static ?string $model = Sender::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Messaging;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'sender_id';

    public static function table(Table $table): Table
    {
        return SendersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSenders::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('users', fn ($query) => $query->where('user_id', auth()->id()));
    }
}
