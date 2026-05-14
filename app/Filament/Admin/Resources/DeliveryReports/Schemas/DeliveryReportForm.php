<?php

namespace App\Filament\Admin\Resources\DeliveryReports\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DeliveryReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Delivery Report Details')->schema([
                    Select::make('gateway_id')->relationship('gateway', 'name')->required(),
                    Select::make('message_id')->relationship('message', 'id')->nullable(),
                    TextInput::make('gateway_message_id')->maxLength(255),
                    TextInput::make('status')->required()->maxLength(255),
                    DateTimePicker::make('received_at')->required(),
                    KeyValue::make('raw_payload')->required()->columnSpanFull(),
                ])->columns(2),
            ]);
    }
}
