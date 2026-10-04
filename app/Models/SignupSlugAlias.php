<?php

namespace App\Models;

use App\Models\Scopes\ScopedBySp;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An old (or reserved) signup slug.
 *
 * tariff_plan_id set  -> old slug of a live plan; the link redirects to the plan's current slug.
 * tariff_plan_id null -> the plan was deleted; the slug stays reserved so it can never be reused.
 */
#[Guarded(['id'])]
class SignupSlugAlias extends Model
{
    public function tariffPlan(): BelongsTo
    {
        return $this->belongsTo(TariffPlan::class)
            ->withoutGlobalScopes([ScopedBySp::class]);
    }
}
