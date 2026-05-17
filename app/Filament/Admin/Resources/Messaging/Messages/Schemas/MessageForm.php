<?php

namespace App\Filament\Admin\Resources\Messaging\Messages\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Message Details')->schema([
                    Select::make('user_id')->relationship('user', 'name')->required(),
                    Select::make('campaign_id')->relationship('campaign', 'name')->nullable(),
                    Select::make('sender_id')->relationship('sender', 'sender_id')->nullable(),
                    Select::make('gateway_id')->relationship('gateway', 'name')->nullable(),
                    TextInput::make('to')->required()->maxLength(255),
                    TextInput::make('status')->required()->default('pending')->maxLength(255),
                    TextInput::make('gateway_message_id')->maxLength(255),
                    TextInput::make('cost')->numeric()->default(0),
                    Textarea::make('body')->required()->columnSpanFull(),
                    Textarea::make('failed_reason')->columnSpanFull(),
                ])->columns(2),
            ]);
    }
}
