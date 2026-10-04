<?php

namespace App\Filament\Admin\Resources\Settings\Senders\Actions;

use App\Actions\Senders\RejectSenderRequest;
use App\Enums\SenderApprovalStatus;
use App\Models\Sender;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

class RejectSenderAction
{
    public static function make(?string $name = 'rejectSender'): Action
    {
        return Action::make($name)
            ->label('Reject')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->modalHeading('Reject Sender Request')
            ->modalDescription('Provide a reason. The S P will see this on their sender request.')
            ->modalWidth(Width::Medium)
            ->visible(fn (Sender $record): bool => $record->approval_status === SenderApprovalStatus::Pending)
            ->schema([
                Textarea::make('rejection_reason')
                    ->label('Rejection Reason')
                    ->required()
                    ->rows(3)
                    ->maxLength(255),
            ])
            ->action(function (array $data, Sender $record): void {
                try {
                    app(RejectSenderRequest::class)->handle(
                        $record,
                        auth()->user(),
                        (string) $data['rejection_reason'],
                    );

                    Notification::make()
                        ->title('Sender Rejected')
                        ->body("Sender {$record->sender_id} was rejected.")
                        ->success()
                        ->send();

                    if ($record->requestedBy) {
                        Notification::make()
                            ->title('Sender ID Rejected')
                            ->body("Your sender ID {$record->sender_id} was rejected: {$data['rejection_reason']}")
                            ->danger()
                            ->sendToDatabase($record->requestedBy);
                    }
                } catch (InvalidArgumentException $exception) {
                    Notification::make()
                        ->title('Rejection Failed')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
