<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BatchApiController extends Controller
{
    /**
     * List all batches for the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Batch::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->with(['sender:id,sender_id'])
            ->latest('id');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));
        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(fn (Batch $b): array => [
            'id' => $b->id,
            'sender_id' => $b->sender?->sender_id ?? '—',
            'status' => $b->status instanceof \BackedEnum ? $b->status->value : (string) $b->status,
            'approval' => $b->approval instanceof \BackedEnum ? $b->approval->value : (string) $b->approval,
            'source_type' => (string) ($b->source_type ?? 'manual'),
            'total_recipients' => (int) ($b->total_recipients ?? count($b->source_config['recipients'] ?? [])),
            'sent_count' => (int) ($b->sent_count ?? 0),
            'failed_count' => (int) ($b->failed_count ?? 0),
            'cost' => (string) number_format((float) ($b->cost ?? 0), 2),
            'scheduled_at' => $b->scheduled_at?->toISOString(),
            'created_at' => $b->created_at?->toISOString() ?? now()->toISOString(),
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
     * Show a single batch with its recent recipient messages.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Batch $batch */
        $batch = Batch::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->with(['sender:id,sender_id'])
            ->findOrFail($id);

        $messages = Message::withoutGlobalScopes()
            ->where('batch_id', $batch->id)
            ->latest('id')
            ->limit(100)
            ->get(['id', 'to', 'body', 'status', 'cost', 'error_message', 'sent_at', 'delivered_at'])
            ->map(fn (Message $m): array => [
                'id' => $m->id,
                'to' => (string) $m->to,
                'body' => (string) $m->body,
                'status' => $m->status instanceof \BackedEnum ? $m->status->value : (string) $m->status,
                'cost' => (float) $m->cost,
                'error_message' => $m->error_message,
                'sent_at' => $m->sent_at?->toISOString(),
                'delivered_at' => $m->delivered_at?->toISOString(),
            ]);

        $totalRecipients = (int) ($batch->total_recipients ?: count($batch->source_config['recipients'] ?? []));

        return new JsonResponse([
            'data' => [
                'batch' => [
                    'id' => $batch->id,
                    'sender_id' => $batch->sender?->sender_id ?? '—',
                    'status' => $batch->status instanceof \BackedEnum ? $batch->status->value : (string) $batch->status,
                    'approval' => $batch->approval instanceof \BackedEnum ? $batch->approval->value : (string) $batch->approval,
                    'source_type' => (string) ($batch->source_type ?? 'manual'),
                    'body' => (string) $batch->body,
                    'total_recipients' => $totalRecipients,
                    'sent_count' => (int) ($batch->sent_count ?? 0),
                    'delivered_count' => (int) ($batch->delivered_count ?? 0),
                    'failed_count' => (int) ($batch->failed_count ?? 0),
                    'cost' => (string) number_format((float) ($batch->cost ?? 0), 2),
                    'scheduled_at' => $batch->scheduled_at?->toISOString(),
                    'created_at' => $batch->created_at?->toISOString() ?? now()->toISOString(),
                ],
                'messages' => $messages,
            ],
        ]);
    }
}
