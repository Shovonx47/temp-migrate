<?php

namespace App\Filament\Admin\Resources\Settings\Senders\Schemas;

use App\Enums\SenderType;
use App\Filament\Admin\Resources\Users\Customers\Tables\CustomersTable;
use Filament\Forms\Components\ModalTableSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SenderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sender Details')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('gateway_id')
                                ->label('Gateway')
                                ->relationship('gateway', 'name')
                                ->required(),
                            TextInput::make('sender_id')
                                ->label('Sender ID')
                                ->required()
                                ->maxLength(255),
                            Select::make('type')
                                ->label('Type')
                                ->options(SenderType::class)
                                ->required(),
                            Select::make('is_enabled')
                                ->label('Enabled')
                                ->boolean()
                                ->default(false),

                            Fieldset::make('Assigned Customers')
                                ->columnSpanFull()
                                ->schema([
                                    ModalTableSelect::make('users')
                                        ->hiddenLabel()
                                        ->multiple()
                                        ->relationship(
                                            'users',
                                            'name',
                                            modifyQueryUsing: function ($query) {
                                                $query->where('type', 'customer');
                                            }
                                        )
                                        ->tableConfiguration(CustomersTable::class)
                                ])
                        ]),
                    ]),
            ]);
    }
}
