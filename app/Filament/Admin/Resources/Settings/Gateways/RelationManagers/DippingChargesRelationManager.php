<?php

namespace App\Filament\Admin\Resources\Settings\Gateways\RelationManagers;

use App\Enums\DippingRateMode;
use App\Models\Operator;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DippingChargesRelationManager extends RelationManager
{
    protected static string $relationship = 'dippingCharges';

    protected static ?string $title = 'Dipping Charges';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('operator.name')
                    ->label('Operator')
                    ->placeholder('Gateway-Wide Default')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rate_mode')
                    ->label('Rate Mode')
                    ->badge(),
                TextColumn::make('masking_rate')
                    ->label('Masking Rate')
                    ->numeric(decimalPlaces: 4)
                    ->prefix(fn ($record) => $record->rate_mode === DippingRateMode::Percentage ? '' : '৳')
                    ->suffix(fn ($record) => $record->rate_mode === DippingRateMode::Percentage ? '%' : '')
                    ->placeholder('—'),
                TextColumn::make('non_masking_rate')
                    ->label('Non-Masking Rate')
                    ->numeric(decimalPlaces: 4)
                    ->prefix(fn ($record) => $record->rate_mode === DippingRateMode::Percentage ? '' : '৳')
                    ->suffix(fn ($record) => $record->rate_mode === DippingRateMode::Percentage ? '%' : '')
                    ->placeholder('—'),
                TextColumn::make('effective_from')
                    ->label('Effective From')
                    ->date()
                    ->placeholder('Immediate / All Time')
                    ->sortable(),
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
                    ->modalHeading('Configure Dipping Charge'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('operator_id')
                ->label('Operator (Optional - Gateway Wide if empty)')
                ->options(Operator::query()->active()->with('country')->get()->mapWithKeys(
                    fn ($op) => [$op->id => $op->name.($op->country ? ' ('.$op->country->name.')' : '')]
                ))
                ->searchable()
                ->nullable()
                ->helperText('Leave empty to apply this dipping rate as a gateway-wide default for all operators without an explicit override.'),
            Select::make('rate_mode')
                ->label('Rate Mode')
                ->options(DippingRateMode::class)
                ->default(DippingRateMode::PerSms->value)
                ->required(),
            TextInput::make('masking_rate')
                ->label('Masking Rate')
                ->numeric()
                ->step(0.0001)
                ->nullable()
                ->helperText('E.g., 0.055 for 5.5 poisha/SMS in Per-SMS mode, or 10 for 10% in Percentage mode.'),
            TextInput::make('non_masking_rate')
                ->label('Non-Masking Rate')
                ->numeric()
                ->step(0.0001)
                ->nullable()
                ->helperText('E.g., 0.045 for 4.5 poisha/SMS in Per-SMS mode, or 10 for 10% in Percentage mode.'),
            DatePicker::make('effective_from')
                ->label('Effective From Date')
                ->nullable()
                ->helperText('Changing a rate with a future/new date ensures historical billing runs preserve past rates accurately.'),
            Textarea::make('notes')
                ->label('Notes')
                ->columnSpanFull()
                ->nullable(),
        ]);
    }
}
