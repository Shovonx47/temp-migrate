<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\SenderApprovalStatus;
use App\Enums\SenderType;
use App\Http\Controllers\Controller;
use App\Models\Sender;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SenderApiController extends Controller
{
    /**
     * List all approved and requested sender IDs for the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Sender::withoutGlobalScopes()
            ->where(function ($q) use ($user): void {
                $q->whereHas('users', fn ($sub) => $sub->where('users.id', $user->id))
                    ->orWhere('requested_by', $user->id);
            });

        if ($search = $request->input('search')) {
            $query->where('sender_id', 'like', "%{$search}%");
        }

        $perPage = max(1, min((int) $request->input('per_page', 50), 100));
        $paginator = $query->latest('id')->paginate($perPage);

        $items = collect($paginator->items())->map(fn (Sender $s): array => [
            'id' => $s->id,
            'sender_id' => (string) $s->sender_id,
            'type' => $s->type instanceof SenderType ? $s->type->value : (string) $s->type,
            'approval_status' => $s->approval_status instanceof SenderApprovalStatus ? $s->approval_status->value : (string) $s->approval_status,
            'is_enabled' => (bool) $s->is_enabled,
            'created_at' => $s->created_at?->toISOString() ?? now()->toISOString(),
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
     * Request a new Sender ID.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'sender_id' => ['required', 'string', 'max:11', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'type' => ['required', 'in:masking,non_masking'],
        ]);

        $senderLabel = strtoupper(trim((string) $validated['sender_id']));

        // Check if user already has this sender or requested it
        $existing = Sender::withoutGlobalScopes()
            ->where('sender_id', $senderLabel)
            ->where(function ($q) use ($user): void {
                $q->whereHas('users', fn ($sub) => $sub->where('users.id', $user->id))
                    ->orWhere('requested_by', $user->id);
            })
            ->first();

        if ($existing) {
            return new JsonResponse([
                'message' => 'You have already registered or requested this Sender ID.',
            ], 422);
        }

        $type = $validated['type'] === 'masking' ? SenderType::Masking : SenderType::NonMasking;

        $sender = Sender::create([
            'sender_id' => $senderLabel,
            'type' => $type,
            'approval_status' => SenderApprovalStatus::Pending,
            'requested_by' => $user->id,
            'is_enabled' => false,
        ]);

        $user->senders()->syncWithoutDetaching([$sender->id]);

        return new JsonResponse([
            'message' => 'Sender ID submitted for approval.',
            'data' => [
                'id' => $sender->id,
                'sender_id' => $sender->sender_id,
                'type' => $sender->type->value,
                'approval_status' => $sender->approval_status->value,
                'is_enabled' => false,
                'created_at' => $sender->created_at?->toISOString(),
            ],
        ], 201);
    }
}
