<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;

#[Group('Account')]
class BalanceController extends Controller
{
    #[Endpoint('Get the current balance')]
    #[Response(content: [
        'balance' => 1250.5,
        'currency' => 'BDT',
    ], status: 200)]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return new JsonResponse([
            'balance' => (float) $user->balance,
            'currency' => 'BDT',
        ]);
    }
}
