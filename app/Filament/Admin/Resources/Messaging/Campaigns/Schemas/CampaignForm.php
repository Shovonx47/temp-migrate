<?php

namespace App\Filament\Admin\Resources\Messaging\Campaigns\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                    Select::make('user_id')->relationship('user', 'name')->required(),
                    Select::make('template_id')->relationship('template', 'name')->nullable(),
                    Select::make('sender_id')->relationship('sender', 'sender_id')->nullable(),
                    TextInput::make('name')->required()->maxLength(255),
                    Textarea::make('body')->required()->columnSpanFull(),
                    Select::make('status')
                        ->options([
                            'draft' => 'Draft', 'scheduled' => 'Scheduled', 'running' => 'Running',
                            'paused' => 'Paused', 'completed' => 'Completed', 'failed' => 'Failed',
                        ])->required()->default('draft'),
                    DateTimePicker::make('scheduled_at'),
                    DateTimePicker::make('started_at'),
                    DateTimePicker::make('completed_at'),
                ])->columns(2),
            ]);
    }
}
