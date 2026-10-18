<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeveloperApiController extends Controller
{
    /**
     * List all API access tokens for the authenticated customer.
     */
    public function tokens(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tokens = $user->tokens()->latest()->get()->map(fn ($t): array => [
            'id' => $t->id,
            'name' => (string) $t->name,
            'last_used_at' => $t->last_used_at?->toISOString(),
            'expires_at' => $t->expires_at?->toISOString(),
            'created_at' => $t->created_at?->toISOString() ?? now()->toISOString(),
        ]);

        return new JsonResponse(['data' => $tokens]);
    }

    /**
     * Generate a new Personal Access API token.
     */
    public function storeToken(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $name = trim((string) $validated['name']);
        $newAccessToken = $user->createToken($name);

        return new JsonResponse([
            'token' => $newAccessToken->plainTextToken,
            'data' => [
                'id' => $newAccessToken->accessToken->id,
                'name' => $newAccessToken->accessToken->name,
                'last_used_at' => null,
                'expires_at' => null,
                'created_at' => $newAccessToken->accessToken->created_at?->toISOString() ?? now()->toISOString(),
            ],
        ], 201);
    }

    /**
     * Revoke an API token.
     */
    public function destroyToken(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->tokens()->where('id', $id)->delete();

        return new JsonResponse(['message' => 'Token revoked successfully.'], 200);
    }
}
