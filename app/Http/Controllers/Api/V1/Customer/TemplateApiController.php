<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateApiController extends Controller
{
    /**
     * List all SMS templates for the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Template::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->latest('id');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));
        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(fn (Template $t): array => [
            'id' => $t->id,
            'name' => (string) $t->name,
            'body' => (string) $t->body,
            'variables' => (array) ($t->variables ?? []),
            'status' => 'approved',
            'created_at' => $t->created_at?->toISOString() ?? now()->toISOString(),
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
     * Create a new SMS template.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $body = trim((string) $validated['body']);

        // Extract variables formatted as {name}, {due_amount}, etc.
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $body, $matches);
        $variables = array_values(array_unique($matches[1] ?? []));

        $template = Template::create([
            'user_id' => $user->id,
            'name' => trim((string) $validated['name']),
            'body' => $body,
            'variables' => $variables,
        ]);

        return new JsonResponse([
            'message' => 'Template created successfully.',
            'data' => [
                'id' => $template->id,
                'name' => $template->name,
                'body' => $template->body,
                'variables' => $template->variables,
                'status' => 'approved',
                'created_at' => $template->created_at?->toISOString(),
            ],
        ], 201);
    }

    /**
     * Delete an SMS template.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $template = Template::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $template->delete();

        return new JsonResponse(['message' => 'Template deleted successfully.'], 200);
    }
}
