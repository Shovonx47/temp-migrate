<?php

namespace App\Filament\Customer\Resources\Messaging\Campaigns\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Campaign Details')->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    Select::make('template_id')
                        ->relationship('template', 'name')
                        ->required(),
                    Select::make('sender_id')
                        ->relationship('sender', 'sender_id')
                        ->required(),
                    DateTimePicker::make('scheduled_at'),
                ])->columns(2),
            ]);
    }
}
