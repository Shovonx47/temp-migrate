<?php

namespace App\Filament\Customer\Resources\Messaging\Campaigns\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Exists;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')->default(fn () => Auth::id()),

                Section::make('Campaign')
                    ->icon(Heroicon::Megaphone)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('sender_id')
                            ->label('Sender ID')
                            ->prefixIcon(Heroicon::Identification)
                            ->options(fn (): array => Auth::user()
                                ->senders()
                                ->select(['senders.sender_id', 'senders.id'])
                                ->pluck('sender_id', 'id')
                                ->all()
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->exists(
                                table: 'sender_user',
                                column: 'sender_id',
                                modifyRuleUsing: fn (Exists $rule) => $rule->where('user_id', Auth::id())
                            ),

                        Select::make('group_id')
                            ->label('Contact Group')
                            ->prefixIcon(Heroicon::UserGroup)
                            ->options(fn (): array => Auth::user()
                                ->groups()
                                ->latest()
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->all()
                            )
                            ->getSearchResultsUsing(fn (string $search): array => Auth::user()
                                ->groups()
                                ->whereLike('name', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->all()
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->exists(
                                table: 'groups',
                                column: 'id',
                                modifyRuleUsing: fn (Exists $rule) => $rule->where('user_id', Auth::id())
                            ),
                    ]),

                Section::make('Message')
                    ->icon(Heroicon::ChatBubbleLeftRight)
                    ->schema([
                        Textarea::make('body')
                            ->label('Message body')
                            ->placeholder('Hi! Here is our latest offer...')
                            ->rows(5)
                            ->required()
                            ->maxLength(2000)
                            ->live(onBlur: true)
                            ->extraInputAttributes(['data-sms-body' => 'true']),

                        View::make('filament.schemas.components.sms-stats'),
                    ]),

                Section::make('Schedule')
                    ->icon(Heroicon::Clock)
                    ->columns(2)
                    ->schema([
                        Radio::make('send_strategy')
                            ->label('When to send')
                            ->options([
                                'now' => 'Send now',
                                'schedule' => 'Schedule for later',
                            ])
                            ->default('now')
                            ->live()
                            ->required()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        DateTimePicker::make('scheduled_at')
                            ->label('Send at')
                            ->minDate(now())
                            ->seconds(false)
                            ->required(fn (Get $get): bool => $get('send_strategy') === 'schedule')
                            ->visible(fn (Get $get): bool => $get('send_strategy') === 'schedule')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
