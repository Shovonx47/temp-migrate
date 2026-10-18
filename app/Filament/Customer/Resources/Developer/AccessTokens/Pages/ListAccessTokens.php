<?php

namespace App\Filament\Customer\Resources\Developer\AccessTokens\Pages;

use App\Filament\Customer\Resources\Developer\AccessTokens\AccessTokenResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Callout;
use Filament\Support\Enums\Width;

class ListAccessTokens extends ListRecords
{
    protected static string $resource = AccessTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->createAnother(false)
                ->modalWidth(Width::Medium)
                ->action(function (array $data) {
                    $expiresAt = match ($data['expires_at']) {
                        'never' => null,
                        default => now()->addDays($data['expires_at']),
                    };

                    $token = Filament::auth()->user()->createToken(
                        name: $data['name'],
                        expiresAt: $expiresAt
                    );

                    $this->replaceMountedAction('viewToken', ['token' => $token->plainTextToken]);
                }),
            Action::make('viewToken')
                ->label('View Token')
                ->modalWidth(Width::Medium)
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->fillForm(fn (array $arguments): array => [
                    'token' => str($arguments['token'])->after('|')->toString(),
                ])
                ->extraAttributes(['style' => 'display: none;'])
                ->closeModalByClickingAway(false)
                ->schema([
                    TextInput::make('token')
                        ->label('Your New Access Token')
                        ->readOnly()
                        ->copyable()
                        ->columnSpanFull(),
                    Callout::make()
                        ->heading("Head's up!")
                        ->description('This is the only time you will be able to view or copy this token, so be sure to copy it now and store it in a secure location.')
                        ->warning(),
                ]),
        ];
    }
}
