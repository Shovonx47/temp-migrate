<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\Schemas;

use App\Filament\Forms\Components\RecipientUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Exists;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class MessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Hidden::make('variables'),
                Wizard::make([
                    self::composeStep(),
                    self::recipientStep(),
                ])
            ]);
    }

    private static function composeStep()
    {
        return Step::make('Compose')
            ->icon(Heroicon::PencilSquare)
            ->columns()
            ->schema([
                Select::make('sender_id')
                    ->label('Sender ID')
                    ->prefixIcon(Heroicon::Identification)
                    ->options(fn() => Auth::user()
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
                        modifyRuleUsing: fn(Exists $rule) => $rule->where('user_id', Auth::id())
                    ),
                Select::make('source')
                    ->label('Source')
                    ->prefixIcon(Heroicon::Inbox)
                    ->options([
                        'manual' => 'Manual',
                        'group' => 'Group',
                        'upload' => 'Upload',
                    ])
                    ->default('manual')
                    ->required(),
                Textarea::make('body')
                    ->label('Message')
                    ->placeholder('Hi {name}, your order {order_id} is confirmed.')
                    ->columnSpanFull()
                    ->rows(5)
                    ->afterStateUpdatedJs(<<<'JS'
                        const variables = [];
                        const regex = /{(\w+)}/g;
                        let match;
                        while ((match = regex.exec($state)) !== null) {
                            variables.push(match[1]);
                        }

                        $set('variables', variables);
                    JS
                    ),
            ]);
    }

    private static function recipientStep()
    {
        return Step::make('Recipient')
            ->icon(Heroicon::Users)
            ->schema([
                // Manual entry repeater will only be visible if 'Manual' is selected as the source
                Repeater::make('recipients')
                    ->visible(fn(Get $get) => $get('source') === 'manual')
                    ->required(fn(Get $get) => $get('source') === 'group')
                    ->table(function (Get $get) {
                        $columns = [
                            Repeater\TableColumn::make('Phone')
                                ->alignLeft()
                                ->markAsRequired()
                        ];

                        foreach ($get('variables') ?? [] as $variable) {
                            $columns[] = Repeater\TableColumn::make(ucfirst($variable))
                                ->alignLeft();
                        }

                        return $columns;
                    })
                    ->schema(function (Get $get) {
                        $fields = [
                            PhoneInput::make('phone')
                        ];

                        foreach ($get('variables') ?? [] as $variable) {
                            $fields[] = TextInput::make("variables.{$variable}")
                                ->label(ucfirst($variable))
                                ->placeholder("Value for {$variable}");
                        }

                        return $fields;
                    }),

                // Group selection will only be visible if 'Group' is selected as the source
                Select::make('groups')
                    ->label('Groups')
                    ->options(fn(): array => auth()->user()
                        ->groups()
                        ->latest()
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->all()
                    )
                    ->getSearchResultsUsing(fn(string $search): array => auth()->user()
                        ->groups()
                        ->whereLike('name', "%{$search}%")
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->all()
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->visible(fn(Get $get) => $get('source') === 'group')
                    ->required(fn(Get $get) => $get('source') === 'group'),

                // Upload field will only be visible if 'Upload' is selected as the source

                RecipientUpload::make('file')
                    ->visible(fn(Get $get) => $get('source') === 'upload')
                    ->required(fn(Get $get) => $get('source') === 'upload')
                    ->afterStateUpdatedJs(<<<'JS'
                        console.log($state.headers)
                    JS
                    ),
            ]);
    }
}
