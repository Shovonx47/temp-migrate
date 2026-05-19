<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SenderController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $senders = $user->senders()
            ->get(['senders.id', 'senders.sender_id', 'senders.type'])
            ->map(fn ($sender): array => [
                'id' => $sender->id,
                'sender_id' => $sender->sender_id,
                'type' => $sender->type,
            ])
            ->values();

        return new JsonResponse(['data' => $senders]);
    }
}
