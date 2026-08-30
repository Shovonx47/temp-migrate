<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\KycPermission;
use App\Enums\UserType;
use App\Models\KycRolePermission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * KycGate — central authority for all KYC permission checks.
 *
 * Usage anywhere in the codebase:
 *   KycGate::allows(KycPermission::Approve)           // uses auth()->user()
 *   KycGate::allowsFor($user, KycPermission::Edit)    // explicit user
 *
 * Rules:
 *  1. If the user is NOT Superman → always false (not an admin).
 *  2. If the user has NO row in kyc_role_permissions → they are a Super Admin
 *     and implicitly have ALL permissions.
 *  3. If the user HAS a row → check whether the permission is in that row's array.
 */
class KycGate
{
    /**
     * Check if the currently authenticated user has the given KYC permission.
     */
    public static function allows(KycPermission $permission): bool
    {
        return self::allowsFor(auth()->user(), $permission);
    }

    /**
     * Check if a specific user has the given KYC permission.
     */
    public static function allowsFor(?User $user, KycPermission $permission): bool
    {
        if (! $user || $user->type !== UserType::Superman) {
            return false;
        }

        // Cache per user per request to avoid repeated DB hits
        $cacheKey = "kyc_perms_{$user->id}";

        $permissions = Cache::remember($cacheKey, 60, function () use ($user) {
            $row = KycRolePermission::where('user_id', $user->id)->first();

            // No row = Super Admin = all permissions
            if (! $row) {
                return KycPermission::all();
            }

            return $row->permissions ?? [];
        });

        return in_array($permission->value, $permissions, true);
    }

    /**
     * Deny if the given permission is not held.
     * Throws a 403 if the user doesn't have the permission.
     */
    public static function authorize(KycPermission $permission): void
    {
        if (! self::allows($permission)) {
            abort(403, "You do not have the [{$permission->getLabel()}] permission.");
        }
    }

    /**
     * Return true if the user is a Super Admin (Superman with no permission row).
     * Super Admins implicitly have all permissions.
     */
    public static function isSuperAdmin(?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (! $user || $user->type !== UserType::Superman) {
            return false;
        }

        return ! KycRolePermission::where('user_id', $user->id)->exists();
    }

    /**
     * Clear the permission cache for a given user (call after grant/revoke).
     */
    public static function clearCache(int $userId): void
    {
        Cache::forget("kyc_perms_{$userId}");
    }
}
