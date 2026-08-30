<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Customers\RelationManagers;

use App\Models\KycAdminNote;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KycAdminNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'kycAdminNotes';

    protected static ?string $title = 'Internal Admin Notes';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedChatBubbleLeftEllipsis;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('note')
                ->label('Note')
                ->placeholder('Internal note — not visible to the customer.')
                ->required()
                ->rows(4)
                ->maxLength(2000)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('note')
            ->columns([
                TextColumn::make('admin_name')
                    ->label('Written By')
                    ->weight('bold')
                    ->placeholder('Unknown'),

                TextColumn::make('note')
                    ->label('Note')
                    ->words(20)
                    ->tooltip(fn (KycAdminNote $record) => $record->note),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Note')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['admin_id']   = auth()->id();
                        $data['admin_name'] = auth()->user()?->name ?? 'Admin';
                        $data['user_id']    = $this->getOwnerRecord()->id;

                        return $data;
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->heading('Internal Notes (Not visible to customer)');
    }
}
