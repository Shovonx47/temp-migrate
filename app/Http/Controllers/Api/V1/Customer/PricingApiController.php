<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Models\User;
use App\Support\OperatorRateResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PricingApiController extends Controller
{
    /**
     * List active mobile operator tariffs with masking and non-masking rates for the customer.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $resolver = OperatorRateResolver::forUser($user);
        $maskingRates = $resolver->ratesForUser(true);
        $nonMaskingRates = $resolver->ratesForUser(false);

        $operators = Operator::query()
            ->active()
            ->with(['country' => fn ($q) => $q->where('is_active', true)])
            ->get();

        $items = $operators->map(fn (Operator $op): array => [
            'id' => $op->id,
            'name' => (string) $op->name,
            'country' => $op->country?->name ?? 'Bangladesh',
            'codes' => array_map('strval', (array) ($op->codes ?? [])),
            'masking_rate' => isset($maskingRates[$op->id]) ? (float) $maskingRates[$op->id] : (float) ($op->masking_price ?? 0.0),
            'non_masking_rate' => isset($nonMaskingRates[$op->id]) ? (float) $nonMaskingRates[$op->id] : (float) ($op->non_masking_price ?? 0.0),
        ]);

        return new JsonResponse(['data' => $items]);
    }
}
