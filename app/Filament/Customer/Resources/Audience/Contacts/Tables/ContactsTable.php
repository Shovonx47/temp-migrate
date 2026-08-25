<?php

namespace App\Filament\Customer\Resources\Audience\Contacts\Tables;

use App\Filament\Customer\Resources\Audience\Contacts\ContactResource;
use App\Filament\Exports\Customer\ContactExporter;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at'),
            ])
            ->heading('Contacts')
            ->headerActions([
                CreateAction::make()
                    ->label('New contact')
                    ->url(fn (): string => ContactResource::getUrl('create')),
                ExportAction::make('export')
                    ->label('Export Contacts')
                    ->exporter(ContactExporter::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
