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
use Illuminate\Support\Facades\DB;

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
                // Atomic guard: only proceed if this batch is still awaiting approval.
                // Prevents duplicate dispatch when two admins approve simultaneously.
                $affected = DB::table('batches')
                    ->where('id', $record->id)
                    ->where('status', BatchStatus::Pending->value)
                    ->where('approval', SmsApproval::Pending->value)
                    ->update([
                        'status' => BatchStatus::Processing->value,
                        'approval' => SmsApproval::Approved->value,
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                        'approval_note' => $data['approval_note'] ?? null,
                        'updated_at' => now(),
                    ]);

                if ($affected === 0) {
                    Notification::make()
                        ->title('Already processed')
                        ->body('This batch has already been approved or rejected by another admin.')
                        ->warning()
                        ->send();

                    return;
                }

                // Refresh the model so downstream code sees the updated state.
                $record->refresh();

                ProcessBatchJob::dispatch(
                    $record,
                    $record->source_type instanceof BatchSource
                        ? $record->source_type->value
                        : (string) $record->source_type,
                    $record->source_config ?? [],
                    $record->body,
                    $record->extractBodyVariables(),
                )->afterCommit();

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
