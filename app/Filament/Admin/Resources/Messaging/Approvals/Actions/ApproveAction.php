<?php

namespace App\Filament\Admin\Resources\Messaging\Approvals\Actions;

use App\Enums\BatchSource;
use App\Enums\BatchStatus;
use App\Enums\SmsApproval;
use App\Jobs\ProcessBatchJob;
use App\Models\Batch;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ApproveAction
{
    public static function make(?string $name = 'approve'): Action
    {
        return Action::make($name)
            ->icon(Heroicon::OutlinedCheckCircle)
            ->label('Approve')
            ->color('success')
            ->modalHeading('Approve SMS Batch')
            ->modalDescription('The batch will be dispatched for sending immediately after approval.')
            ->modalWidth(Width::Medium)
            ->schema([
                Textarea::make('approval_note')
                    ->label('Note (optional)')
                    ->placeholder('Add an internal note for this approval…')
                    ->rows(3),
            ])
            ->action(function (array $data, Batch $record): void {
                $record->update([
                    'status' => BatchStatus::Processing,
                    'approval' => SmsApproval::Approved,
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                    'approval_note' => $data['approval_note'] ?? null,
                ]);

                ProcessBatchJob::dispatch(
                    $record,
                    $record->source_type instanceof BatchSource
                        ? $record->source_type->value
                        : (string) $record->source_type,
                    $record->source_config ?? [],
                    $record->body,
                    $record->extractBodyVariables(),
                )->onQueue('batches');

                Notification::make()
                    ->title('Batch Approved')
                    ->body('The SMS batch has been approved and dispatched for sending.')
                    ->success()
                    ->send();

                Notification::make()
                    ->title('Your SMS batch has been approved')
                    ->body('Your message batch is now being processed and will be sent shortly.')
                    ->success()
                    ->sendToDatabase($record->user);
            });
    }
}
