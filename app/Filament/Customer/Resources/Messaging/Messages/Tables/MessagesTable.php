<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\Tables;

use App\Enums\MessageStatus;
use App\Filament\Exports\MessageExporter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class MessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('to')
                    ->label('Recipient')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('cost')
                    ->money('BDT'),
                TextColumn::make('body')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->body),
                TextColumn::make('campaign.name')
                    ->placeholder('-'),
                TextColumn::make('sent_at'),
                TextColumn::make('created_at'),
            ])
            ->poll('30s')
            ->groups([
                Group::make('status')->collapsible(),
            ])
            ->collapsedGroupsByDefault()
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                ]),
            ])
            ->heading('Messages')
            ->headerActions([
                ExportAction::make()
                    ->exporter(MessageExporter::class),
            ])
            ->filters([
                SelectFilter::make('sender_id')
                    ->label('Sender')
                    ->relationship(
                        name: 'sender',
                        titleAttribute: 'sender_id',
                        modifyQueryUsing: fn ($query) => $query->accessible()
                    ),
                SelectFilter::make('status')
                    ->options(MessageStatus::class),
                Filter::make('created_at')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('Created From'),
                        DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function ($query, $data) {
                        if ($data['created_from'] ?? null) {
                            $query->whereDate('created_at', '>=', $data['created_from']);
                        }

                        if ($data['created_until'] ?? null) {
                            $query->whereDate('created_at', '<=', $data['created_until']);
                        }
                    }),
            ], layout: FiltersLayout::AboveContent);
    }
}
