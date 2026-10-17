<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return new JsonResponse([
            'data' => CustomerUserResource::make($user)->resolve($request),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user->update($validated);

        return new JsonResponse([
            'data' => CustomerUserResource::make($user->fresh())->resolve($request),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password:customer'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user->update([
            'password' => $validated['password'],
        ]);

        return new JsonResponse([
            'message' => 'Password updated.',
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $from = now()->startOfDay();
        $to = now();

        $stats = $user->messages()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('sent', 'delivered') THEN 1 ELSE 0 END), 0) as successful")
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('failed', 'dlr_failed') THEN 1 ELSE 0 END), 0) as failed")
            ->selectRaw("COALESCE(SUM(CASE WHEN status NOT IN ('failed', 'dlr_failed') THEN cost ELSE 0 END), 0) as cost")
            ->first();

        $total = (int) ($stats->total ?? 0);
        $successful = (int) ($stats->successful ?? 0);

        return new JsonResponse([
            'data' => [
                'messages_today' => $total,
                'successful_today' => $successful,
                'failed_today' => (int) ($stats->failed ?? 0),
                'cost_today' => round((float) ($stats->cost ?? 0), 2),
                'delivery_rate' => $total > 0 ? round(($successful / $total) * 100, 1) : 0,
                'contacts_total' => $user->contacts()->count(),
            ],
        ]);
    }
}
