<?php

namespace App\Filament\Customer\Resources\Messaging\MessageBatches\RelationManagers;

use App\Filament\Customer\Resources\Messaging\Messages\MessageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $relatedResource = MessageResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
