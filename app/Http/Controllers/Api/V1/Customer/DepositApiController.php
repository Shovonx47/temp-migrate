<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\DepositPaymentMethod;
use App\Enums\DepositStatus;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\User;
use App\Services\Payment\Data\PaymentRequest;
use App\Services\Payment\Data\PaymentResponse;
use App\Support\Facades\Payment;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepositApiController extends Controller
{
    /**
     * List all deposits for the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Deposit::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->latest('id');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));
        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(fn (Deposit $d): array => [
            'id' => $d->id,
            'transaction_id' => $d->transaction_id ?? "DEP-{$d->id}",
            'amount' => (string) number_format((float) $d->amount, 2),
            'payment_method' => $d->payment_method instanceof DepositPaymentMethod ? $d->payment_method->value : (string) $d->payment_method,
            'status' => $d->status instanceof DepositStatus ? $d->status->value : (string) $d->status,
            'created_at' => $d->created_at?->toISOString() ?? now()->toISOString(),
            'paid_at' => $d->paid_at?->toISOString(),
        ]);

        return new JsonResponse([
            'data' => $items,
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Initiate a new deposit with payment gateway.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:50', 'max:100000'],
            'payment_method' => ['nullable', 'string'],
        ]);

        $amount = (float) $validated['amount'];

        if ($user->type->isCustomer() && $user->sp_id) {
            $sp = $user->sp;
            if (! $sp || (float) $sp->balance < $amount) {
                return new JsonResponse([
                    'message' => 'Your reseller does not have sufficient balance for this top-up. Please contact your reseller.',
                ], 422);
            }
        }

        try {
            $response = DB::transaction(function () use ($amount, $user): PaymentResponse {
                $gateway = 'piprapay';

                /** @var Deposit $deposit */
                $deposit = $user->deposits()->create([
                    'amount' => $amount,
                    'currency' => 'BDT',
                    'payment_method' => DepositPaymentMethod::PipraPay,
                    'status' => DepositStatus::Pending,
                ]);

                $response = Payment::driver($gateway)
                    ->create(new PaymentRequest(
                        orderId: $deposit->id,
                        amount: $amount,
                        currency: 'BDT',
                        customerName: $user->name,
                        customerEmail: $user->email,
                        customerPhone: $user->phone ?? '',
                        returnUrl: route('payments.callback', ['gateway' => $gateway, 'deposit' => $deposit->id]),
                        webhookUrl: route('payments.webhook', ['gateway' => $gateway]),
                        metadata: ['order_id' => $deposit->id]
                    ));

                if ($response->successful) {
                    $deposit->update([
                        'transaction_id' => $response->gatewayPaymentId,
                        'meta' => $response->raw,
                    ]);

                    return $response;
                }

                throw new Exception("Payment gateway error: {$response->errorMessage}");
            });

            return new JsonResponse([
                'success' => true,
                'redirect_url' => $response->checkoutUrl,
            ], 200);
        } catch (Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
