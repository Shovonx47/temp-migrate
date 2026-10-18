<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactApiController extends Controller
{
    /**
     * List contacts for the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Contact::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->with(['groups:id,name'])
            ->latest('id');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('phone', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($groupId = $request->input('group_id')) {
            $query->whereHas('groups', fn ($q) => $q->where('groups.id', $groupId));
        }

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));
        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(fn (Contact $c): array => [
            'id' => $c->id,
            'name' => (string) ($c->name ?? ''),
            'phone' => (string) $c->phone,
            'group' => $c->groups->first()?->name ?? null,
            'group_id' => $c->groups->first()?->id ?? null,
            'created_at' => $c->created_at?->toISOString() ?? now()->toISOString(),
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
     * Store a new contact.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'group_id' => ['nullable', 'integer'],
        ]);

        $contact = Contact::create([
            'user_id' => $user->id,
            'name' => trim((string) ($validated['name'] ?? '')),
            'phone' => trim((string) $validated['phone']),
        ]);

        if (! empty($validated['group_id'])) {
            $group = $user->groups()->find($validated['group_id']);
            if ($group) {
                $contact->groups()->attach($group->id);
            }
        }

        return new JsonResponse([
            'message' => 'Contact added successfully.',
            'data' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'phone' => (string) $contact->phone,
                'group' => $contact->groups()->first()?->name,
                'created_at' => $contact->created_at?->toISOString(),
            ],
        ], 201);
    }

    /**
     * Delete a contact.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $contact = Contact::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $contact->delete();

        return new JsonResponse(['message' => 'Contact deleted successfully.'], 200);
    }

    /**
     * List contact groups for the authenticated customer.
     */
    public function groups(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Group::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->withCount('contacts')
            ->latest('id');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));
        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(fn (Group $g): array => [
            'id' => $g->id,
            'name' => (string) $g->name,
            'contacts_count' => (int) ($g->contacts_count ?? 0),
            'created_at' => $g->created_at?->toISOString() ?? now()->toISOString(),
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
     * Store a new contact group.
     */
    public function storeGroup(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $group = Group::create([
            'user_id' => $user->id,
            'name' => trim((string) $validated['name']),
        ]);

        return new JsonResponse([
            'message' => 'Contact group created.',
            'data' => [
                'id' => $group->id,
                'name' => $group->name,
                'contacts_count' => 0,
                'created_at' => $group->created_at?->toISOString(),
            ],
        ], 201);
    }

    /**
     * Delete a contact group.
     */
    public function destroyGroup(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $group = Group::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $group->delete();

        return new JsonResponse(['message' => 'Contact group deleted.'], 200);
    }
}
