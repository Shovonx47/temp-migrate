<?php

namespace App\Filament\Customer\Resources\Developer\AccessTokens\Tables;

use App\Models\AccessToken;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class AccessTokensTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Token Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_used_at')
                    ->label('Last Used')
                    ->placeholder('Never Used')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expires At')
                    ->placeholder('Never Expires')
                    ->dateTime('M d, Y \a\t g:i A')
                    ->sinceTooltip()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),

                DeleteAction::make()
                    ->requiresConfirmation()
                    ->schema(fn (AccessToken $record) => [
                        Callout::make()
                            ->description(new HtmlString(
                                'This action will permanently delete the access token <strong>'.
                                e($record->name).
                                '</strong>. To continue, type the token name below.'
                            ))
                            ->danger(),

                        TextInput::make('name')
                            ->label('Token name')
                            ->placeholder($record->name)
                            ->required(),
                    ])
                    ->action(function (DeleteAction $action, AccessToken $record, array $data) {
                        if ($record->name !== $data['name']) {
                            Notification::make()
                                ->title('Token name does not match.')
                                ->danger()
                                ->send();

                            $action->halt();
                        }

                        $record->delete();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
