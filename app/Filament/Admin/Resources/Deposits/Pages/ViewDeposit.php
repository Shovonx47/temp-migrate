<?php

namespace App\Filament\Admin\Resources\Deposits\Pages;

use App\Enums\DepositStatus;
use App\Filament\Admin\Resources\Deposits\DepositResource;
use App\Services\BalanceService;
use App\Services\Payments\PaymentGatewayResolver;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

class ViewDeposit extends ViewRecord
{
    protected static string $resource = DepositResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            Action::make('refund')
                ->label('Refund via PipraPay')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === DepositStatus::Completed
                    && $this->record->payment_method === 'piprapay')
                ->action(function (): void {
                    $deposit = $this->record;

                    try {
                        $resolver = app(PaymentGatewayResolver::class);
                        $driver = $resolver->resolve('piprapay');
                        $driver->refund($deposit->transaction_id);
                    } catch (\Throwable $e) {
                        Log::error('PipraPay refund failed', [
                            'deposit_id' => $deposit->id,
                            'pp_id' => $deposit->transaction_id,
                            'error' => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->title('Refund failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    $deposit->update(['status' => DepositStatus::Refunded]);

                    app(BalanceService::class)->debit(
                        user: $deposit->user,
                        amount: (float) $deposit->amount,
                        transactionable: $deposit,
                        description: 'Refund via PipraPay',
                    );

                    Notification::make()
                        ->title('Refund processed')
                        ->success()
                        ->send();
                }),
        ];
    }
}
