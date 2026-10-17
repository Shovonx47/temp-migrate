<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\TransactionDirection;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionApiController extends Controller
{
    /**
     * List all financial transactions for the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Transaction::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->latest('id');

        if ($type = $request->input('type')) {
            if ($type === 'credit') {
                $query->where('direction', TransactionDirection::Credit);
            } elseif ($type === 'debit') {
                $query->where('direction', TransactionDirection::Debit);
            }
        }

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));
        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(fn (Transaction $t): array => [
            'id' => $t->id,
            'type' => $t->direction instanceof TransactionDirection ? $t->direction->value : (string) $t->direction,
            'amount' => (string) number_format((float) $t->amount, 2),
            'balance_before' => (string) number_format((float) $t->balance_before, 2),
            'balance_after' => (string) number_format((float) $t->balance_after, 2),
            'description' => $t->description ?? ($t->transactionable_type ? class_basename($t->transactionable_type) : 'Balance Adjustment'),
            'created_at' => $t->created_at?->toISOString() ?? now()->toISOString(),
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
}
