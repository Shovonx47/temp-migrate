<?php

namespace App\Filament\Sp\Resources\Messaging\Senders\Schemas;

use App\Enums\SenderType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SenderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Request Sender ID')
                    ->description('Submit a Sender ID for Superman approval. It cannot be used until approved and a gateway is assigned.')
                    ->schema([
                        TextInput::make('sender_id')
                            ->label('Sender ID')
                            ->placeholder('e.g. 8809617639963 or BrandName')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Select::make('type')
                            ->label('Type')
                            ->options(SenderType::maskingCategoryOptions())
                            ->default(SenderType::NonMaskingCategory)
                            ->required()
                            ->helperText('Masking = alphanumeric brand name. Non-masking = numeric CLI.'),
                    ])
                    ->columns(2),
            ]);
    }
}
