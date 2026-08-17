<?php

namespace App\Filament\Admin\Resources\Settings\Senders\Actions;

use App\Actions\Senders\ApproveSenderRequest;
use App\Enums\SenderApprovalStatus;
use App\Models\Gateway;
use App\Models\Sender;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

class ApproveSenderAction
{
    public static function make(?string $name = 'approveSender'): Action
    {
        return Action::make($name)
            ->label('Approve')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->modalHeading('Approve Sender Request')
            ->modalDescription('Assign a gateway and approve this S P sender request. It will become usable once approved.')
            ->modalWidth(Width::Medium)
            ->visible(fn (Sender $record): bool => $record->approval_status === SenderApprovalStatus::Pending)
            ->schema([
                Select::make('gateway_id')
                    ->label('Gateway')
                    ->options(fn (): array => Gateway::query()
                        ->where('is_enabled', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
            ])
            ->action(function (array $data, Sender $record): void {
                try {
                    app(ApproveSenderRequest::class)->handle(
                        $record,
                        auth()->user(),
                        (int) $data['gateway_id'],
                    );

                    Notification::make()
                        ->title('Sender Approved')
                        ->body("Sender {$record->sender_id} is approved and enabled.")
                        ->success()
                        ->send();

                    if ($record->requestedBy) {
                        Notification::make()
                            ->title('Sender ID Approved')
                            ->body("Your sender ID {$record->sender_id} was approved.")
                            ->success()
                            ->sendToDatabase($record->requestedBy);
                    }
                } catch (InvalidArgumentException $exception) {
                    Notification::make()
                        ->title('Approval Failed')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
