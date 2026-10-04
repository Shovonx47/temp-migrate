<?php

namespace App\Services;

use App\Enums\TariffPlanStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Scopes\ScopedBySp;
use App\Models\SignupSlugAlias;
use App\Models\TariffPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Single source of truth for signup slugs: generation, uniqueness, and public resolution.
 * Used by the model hooks, the Filament forms, the public controller, and the Register page.
 */
class TariffSlugService
{
    public const SESSION_KEY = 'signup_plan_slug';

    /** Lowercase letters and digits, separated by single hyphens. */
    public const PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public const MAX_LENGTH = 64;

    /**
     * Generate a slug from a name that is unique across plans AND aliases.
     * Pass $ignorePlanId when regenerating for an existing plan.
     */
    public static function generate(string $name, ?int $ignorePlanId = null): string
    {
        // Leave room for a "-123" suffix inside MAX_LENGTH.
        $base = trim(substr(Str::slug($name), 0, self::MAX_LENGTH - 8), '-');
        $base = $base === '' ? 'plan' : $base;

        $slug = $base;

        for ($i = 2; self::isTaken($slug, $ignorePlanId); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }

    /**
     * Whether a slug is used by any plan or alias (other than the given plan's own).
     * DB::table() is deliberate: it is never affected by the ScopedBySp global scope,
     * so a logged-in reseller sees every slug in the system.
     */
    public static function isTaken(string $slug, ?int $ignorePlanId = null): bool
    {
        $usedByPlan = DB::table('tariff_plans')
            ->where('slug', $slug)
            ->when($ignorePlanId, fn ($query) => $query->where('id', '!=', $ignorePlanId))
            ->exists();

        return $usedByPlan || self::isAliasTaken($slug, $ignorePlanId);
    }

    /**
     * Whether a slug is reserved as an alias by another plan (or by a deleted plan).
     * A plan's own old slugs are NOT counted, so it can revert to them.
     */
    public static function isAliasTaken(string $slug, ?int $ignorePlanId = null): bool
    {
        return SignupSlugAlias::query()
            ->where('slug', $slug)
            ->when($ignorePlanId, fn ($query) => $query->where(
                fn ($inner) => $inner->whereNull('tariff_plan_id')->orWhere('tariff_plan_id', '!=', $ignorePlanId)
            ))
            ->exists();
    }

    /**
     * Resolve a public slug (or old alias) to a plan that can accept signups.
     *
     * Returns null when: malformed/unknown slug, plan not Active, the plan was deleted,
     * or the plan belongs to a reseller who is not an Approved, non-deleted SP.
     * Callers must treat every null the same way ("This signup link is no longer active.").
     *
     * @return array{plan: TariffPlan, is_alias: bool, canonical_slug: string}|null
     */
    public static function resolve(string $slug): ?array
    {
        $slug = Str::lower(trim($slug));

        if ($slug === '' || strlen($slug) > self::MAX_LENGTH || ! preg_match(self::PATTERN, $slug)) {
            return null;
        }

        // Public request: bypass ScopedBySp explicitly (if a reseller happens to be logged in
        // in the same browser, their scope would otherwise hide other plans).
        $plans = fn () => TariffPlan::query()->withoutGlobalScopes([ScopedBySp::class]);

        $isAlias = false;
        $plan = $plans()->where('slug', $slug)->first();

        if ($plan === null) {
            $alias = SignupSlugAlias::query()->where('slug', $slug)->first();

            if ($alias === null || $alias->tariff_plan_id === null) {
                return null;
            }

            $plan = $plans()->find($alias->tariff_plan_id);
            $isAlias = true;
        }

        if ($plan === null || $plan->status !== TariffPlanStatus::Active) {
            return null;
        }

        // Reseller-owned plan: the reseller must still be an approved, non-deleted SP.
        // Only ScopedBySp is removed, so the SoftDeletes scope still hides deleted resellers.
        if ($plan->sp_id !== null) {
            $reseller = User::query()
                ->withoutGlobalScopes([ScopedBySp::class])
                ->find($plan->sp_id);

            if ($reseller === null
                || $reseller->type !== UserType::Sp
                || $reseller->status !== UserStatus::Approved) {
                return null;
            }
        }

        return [
            'plan' => $plan,
            'is_alias' => $isAlias,
            'canonical_slug' => $plan->slug,
        ];
    }

    public static function signupUrl(TariffPlan $plan): string
    {
        return url('/register/'.$plan->slug);
    }
}
