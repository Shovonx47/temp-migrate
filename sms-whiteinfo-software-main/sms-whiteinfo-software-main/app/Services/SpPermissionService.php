<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserType;
use App\Models\User;
use BackedEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SpPermissionService
{
    /**
     * Check whether the given user has the specified permission.
     */
    public function can(?User $user, string|BackedEnum $permission): bool
    {
        if (! $user) {
            return false;
        }

        $permKey = $permission instanceof BackedEnum ? $permission->value : $permission;

        // Superman panel context
        if ($user->type === UserType::Superman) {
            // Super Admin (no role assigned) has all permissions implicitly
            if ($user->sp_role_id === null) {
                return true;
            }

            return $user->spRole?->hasPermission($permKey) ?? false;
        }

        // S P reseller panel context
        if ($user->type === UserType::Sp) {
            // S P owner (sp_id is null) has all permissions for their own panel
            if ($user->sp_id === null) {
                return true;
            }

            // S P employee: must have a role assigned and permission granted
            if (! $user->sp_role_id) {
                return false;
            }

            return $user->spRole?->hasPermission($permKey) ?? false;
        }

        return false;
    }

    /**
     * Check if user has ANY of the specified permissions.
     *
     * @param  array<string|BackedEnum>  $permissions
     */
    public function canAny(?User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->can($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enforce authorization or abort with 403.
     */
    public function authorize(string|BackedEnum $permission, ?string $guard = null): void
    {
        $user = $guard ? Auth::guard($guard)->user() : (Auth::guard('sp')->user() ?? Auth::guard('superman')->user() ?? Auth::user());

        if (! $this->can($user, $permission)) {
            $label = $permission instanceof BackedEnum ? $permission->value : $permission;
            abort(403, "You do not have the [{$label}] permission.");
        }
    }

    /**
     * Return true if user is a Super Admin (Superman with no role assigned).
     */
    public function isSuperAdmin(?User $user = null): bool
    {
        $user = $user ?? Auth::guard('superman')->user();

        return $user && $user->type === UserType::Superman && $user->sp_role_id === null;
    }

    /**
     * Return true if user is an S P reseller owner.
     */
    public function isSpOwner(?User $user = null): bool
    {
        $user = $user ?? Auth::guard('sp')->user();

        return $user && $user->type === UserType::Sp && $user->sp_id === null;
    }

    /**
     * Return true if user is an S P reseller employee.
     */
    public function isSpEmployee(?User $user = null): bool
    {
        $user = $user ?? Auth::guard('sp')->user();

        return $user && $user->type === UserType::Sp && $user->sp_id !== null;
    }

    /**
     * Clear permission cache if any is cached.
     */
    public function clearCache(int $userId): void
    {
        Cache::forget("user_perms_{$userId}");
    }
}
