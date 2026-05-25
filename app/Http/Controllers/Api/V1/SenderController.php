<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;

#[Group('Senders')]
class SenderController extends Controller
{
    #[Endpoint('List senders available to the authenticated customer', <<<'DESC'
        Returns the sender labels you may pass as `sender_id` when calling `POST /messages` (e.g. `FROLAX`, `DDBL`, `8809...`).
        DESC)]
    #[Response(content: [
        'data' => [
            ['sender_id' => 'FROLAX', 'type' => 'masking'],
            ['sender_id' => '8809612345678', 'type' => 'non_masking'],
        ],
    ], status: 200)]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $senders = $user->senders()
            ->get(['senders.sender_id', 'senders.type'])
            ->map(fn ($sender): array => [
                'sender_id' => $sender->sender_id,
                'type' => $sender->type,
            ])
            ->values();

        return new JsonResponse(['data' => $senders]);
    }
}
