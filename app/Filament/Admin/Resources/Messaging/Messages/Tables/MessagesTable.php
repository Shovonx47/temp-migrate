<?php

namespace App\Filament\Admin\Resources\Messaging\Messages\Tables;

use App\Enums\MessageSource;
use App\Enums\MessageStatus;
use App\Filament\Exports\MessageExporter;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class MessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('to')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('body')
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->body),
                TextColumn::make('status')
                    ->sortable()
                    ->badge()
                    ->summarize(
                        Count::make()
                            ->label('Delivered')
                            ->query(fn ($query) => $query->where('status', MessageStatus::Delivered))
                    ),
                TextColumn::make('cost')
                    ->label('Charged')
                    ->prefix('৳')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                TextColumn::make('operator_cost')
                    ->label('Operator Cost')
                    ->prefix('৳')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                TextColumn::make('gateway_cost')
                    ->label('Gateway Cost')
                    ->prefix('৳')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('sender_id')
                    ->label('Sender ID')
                    ->relationship('sender', 'sender_id')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options(MessageStatus::class)
                    ->searchable()
                    ->preload(),
                SelectFilter::make('source')
                    ->options(MessageSource::class)
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_unicode')
                    ->label('Unicode')
                    ->placeholder('All')
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->nullable(),
                Filter::make('created_at')
                    ->columnSpan(3)
                    ->columns(3)
                    ->schema([
                        PhoneInput::make('to')
                            ->label('To')
                            ->placeholder('Search by recipient number'),
                        DatePicker::make('created_from')
                            ->label('Created From'),
                        DatePicker::make('created_until')
                            ->label('Created Until')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['to'],
                                fn (Builder $query, $to) => $query->whereLike('to', "%{$to}%")
                            )
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date)
                            );
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->headerActions([
                ExportAction::make()
                    ->exporter(MessageExporter::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
