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
class MeController extends Controller
{
    #[Endpoint('Get the authenticated customer profile')]
    #[Response(content: [
        'id' => 1,
        'name' => 'Bishwajit Adhikary',
        'email' => 'bishwajitcadhikary@gmail.com',
        'phone' => '+8801322635808',
        'balance' => 1250.5,
        'approval' => 'approved',
    ], status: 200)]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return new JsonResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'balance' => (float) $user->balance,
            'approval' => $user->status,
        ]);
    }
}
