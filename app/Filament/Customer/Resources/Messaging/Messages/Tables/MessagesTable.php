<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\Tables;

use App\Enums\MessageStatus;
use App\Filament\Exports\MessageExporter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
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
                    ->tooltip(fn($record) => $record->body),
                TextColumn::make('campaign.name')
                    ->placeholder('-'),
                TextColumn::make('sent_at'),
                TextColumn::make('created_at'),
            ])
            ->poll('60s')
            ->groups([
                Group::make('status')->collapsible()
            ])
            ->collapsedGroupsByDefault()
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                ]),
            ])
            ->heading("Messages")
            ->headerActions([
                ExportAction::make()
                    ->exporter(MessageExporter::class)
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(MessageStatus::class),
            ]);
    }
}
