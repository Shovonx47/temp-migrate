<?php

namespace App\Filament\Admin\Resources\Settings\Gateways\RelationManagers;

use App\Enums\DippingRateMode;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ThirdPartyDippingRelationManager extends RelationManager
{
    protected static string $relationship = 'thirdPartyDippingPeriods';

    protected static ?string $title = 'Third-Party Dipping';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) ($ownerRecord->has_third_party_dipping ?? false);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period_start')
                    ->label('Period')
                    ->state(fn ($record) => $record->period_start?->format('M d, Y').' — '.$record->period_end?->format('M d, Y'))
                    ->sortable(['period_start']),
                TextColumn::make('dipped_sms_volume')
                    ->label('Specifically Dipped Volume')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('manual_cost')
                    ->label('Third-Party Cost')
                    ->numeric(decimalPlaces: 2)
                    ->prefix('৳')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('rate')
                    ->label('Configured Rate')
                    ->numeric(decimalPlaces: 4)
                    ->placeholder('—'),
                TextColumn::make('rate_mode')
                    ->label('Mode')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->modalHeading('Add Third-Party Dipping Period'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                DatePicker::make('period_start')
                    ->label('Period Start Date')
                    ->required(),
                DatePicker::make('period_end')
                    ->label('Period End Date')
                    ->required(),
                TextInput::make('dipped_sms_volume')
                    ->label('Dipped SMS Volume (Subset only)')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->helperText('Actual SMS volume that underwent 3rd-party dipping specifically, not total gateway volume.'),
                TextInput::make('manual_cost')
                    ->label('Manual Dipping Cost (৳)')
                    ->numeric()
                    ->required()
                    ->helperText('Authoritative cost billed by 3rd-party dipping vendor.'),
                TextInput::make('rate')
                    ->label('Rate (Optional)')
                    ->numeric()
                    ->nullable(),
                Select::make('rate_mode')
                    ->label('Rate Mode')
                    ->options(DippingRateMode::class)
                    ->default(DippingRateMode::PerSms->value)
                    ->nullable(),
            ]),
            Textarea::make('notes')
                ->label('Notes')
                ->columnSpanFull()
                ->nullable(),
        ]);
    }
}
