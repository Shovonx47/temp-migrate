<?php

namespace App\Filament\Admin\Resources\Messaging\Approvals\Actions;

use App\Enums\MessageBatchStatus;
use App\Enums\SmsApproval;
use App\Models\MessageBatch;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class RejectAction
{
    public static function make(?string $name = 'reject'): Action
    {
        return Action::make($name)
            ->icon(Heroicon::OutlinedXCircle)
            ->label('Reject')
            ->color('danger')
            ->modalHeading('Reject SMS Batch')
            ->modalDescription('The batch will be rejected and the customer will be notified.')
            ->modalWidth(Width::Medium)
            ->schema([
                Textarea::make('approval_note')
                    ->label('Reason for rejection')
                    ->placeholder('Explain why this batch is being rejected…')
                    ->required()
                    ->rows(3),
            ])
            ->action(function (array $data, MessageBatch $record): void {
                $record->update([
                    'status' => MessageBatchStatus::Failed,
                    'approval' => SmsApproval::Rejected,
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                    'approval_note' => $data['approval_note'],
                ]);

                Notification::make()
                    ->title('Batch Rejected')
                    ->body('The SMS batch has been rejected.')
                    ->warning()
                    ->send();

                Notification::make()
                    ->title('Your SMS batch has been rejected')
                    ->body('Reason: '.$data['approval_note'])
                    ->danger()
                    ->sendToDatabase($record->user);
            });
    }
}
