<?php

namespace App\Http\Controllers;

use App\Enums\DepositStatus;
use App\Filament\Customer\Resources\Billing\Deposits\DepositResource;
use App\Models\Deposit;
use Exception;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Payment gateway return URL handler (works for any panel).
     *
     * @throws Exception
     */
    public function __invoke(Request $request, string $gateway, Deposit $deposit)
    {
        abort_if($deposit->payment_method->value !== $gateway, 404);

        $user = $request->user();

        abort_if(! $user || $deposit->user_id !== $user->id, 404);

        $notification = match ($deposit->status) {
            DepositStatus::Pending => [
                'title' => 'Payment Processing',
                'body' => 'Your payment is currently being processed.',
                'icon' => Heroicon::OutlinedClock,
                'color' => 'primary',
            ],
            DepositStatus::Completed => [
                'title' => 'Payment Completed',
                'body' => 'Your payment has been successfully processed.',
                'icon' => Heroicon::OutlinedCheckCircle,
                'color' => 'success',
            ],
            DepositStatus::Failed => [
                'title' => 'Payment Failed',
                'body' => 'Your payment could not be processed.',
                'icon' => Heroicon::OutlinedXCircle,
                'color' => 'danger',
            ],
            DepositStatus::Refunded => [
                'title' => 'Payment Refunded',
                'body' => 'This payment has been refunded.',
                'icon' => Heroicon::OutlinedArrowUturnLeft,
                'color' => 'warning',
            ],
        };

        Notification::make()
            ->title($notification['title'])
            ->body($notification['body'])
            ->icon($notification['icon'])
            ->color($notification['color'])
            ->send();

        $redirectUrl = match (true) {
            $deposit->user->type->isSp() => \App\Filament\Sp\Resources\Billing\Deposits\DepositResource::getUrl('view', [
                'record' => $deposit,
            ], panel: 'sp'),
            $deposit->user->type->isSuperman() => \App\Filament\Admin\Resources\Finance\Deposits\DepositResource::getUrl('view', [
                'record' => $deposit,
            ], panel: 'superman'),
            default => DepositResource::getUrl('view', [
                'record' => $deposit,
            ], panel: 'customer'),
        };

        return redirect($redirectUrl);
    }
}
