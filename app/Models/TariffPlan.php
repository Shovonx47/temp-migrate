<?php

namespace App\Models;

use App\Enums\TariffPlanStatus;
use App\Enums\TariffPlanType;
use App\Models\Scopes\ScopedBySp;
use App\Services\TariffSlugService;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Guarded(['id'])]
#[ScopedBy(ScopedBySp::class)]
class TariffPlan extends Model
{
    protected static function booted(): void
    {
        // Every plan created through Eloquent gets a slug, whichever code path creates it
        // (admin form, SP form, clone action, seeders, tinker).
        static::creating(function (self $plan): void {
            if (blank($plan->slug)) {
                $plan->slug = TariffSlugService::generate((string) $plan->name);
            }
        });

        static::updating(function (self $plan): void {
            // A slug can never be blanked out. Legacy rows without one heal themselves here.
            if (blank($plan->slug)) {
                $plan->slug = $plan->getOriginal('slug')
                    ?: TariffSlugService::generate((string) $plan->name, $plan->id);
            }
        });

        // Slug changed: keep the old link alive as an alias (spec 3.5).
        static::updated(function (self $plan): void {
            if (! $plan->wasChanged('slug')) {
                return;
            }

            $oldSlug = $plan->getOriginal('slug');

            if (filled($oldSlug)) {
                SignupSlugAlias::query()->firstOrCreate(
                    ['slug' => $oldSlug],
                    ['tariff_plan_id' => $plan->id],
                );
            }

            // Reverting to a previous slug: it is canonical again, so it stops being an alias.
            SignupSlugAlias::query()
                ->where('tariff_plan_id', $plan->id)
                ->where('slug', $plan->slug)
                ->delete();
        });

        // Deleted plan: reserve its slug forever so it can never point at a different plan later.
        // (Its existing aliases are set to tariff_plan_id = NULL by the foreign key.)
        static::deleted(function (self $plan): void {
            if (filled($plan->slug)) {
                SignupSlugAlias::query()->firstOrCreate(
                    ['slug' => $plan->slug],
                    ['tariff_plan_id' => null],
                );
            }
        });
    }

    protected function casts(): array
    {
        return [
            'type' => TariffPlanType::class,
            'status' => TariffPlanStatus::class,
        ];
    }

    public function tariffRates(): HasMany
    {
        return $this->hasMany(TariffRate::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TariffPlanAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->hasMany(TariffPlanAssignment::class)->where('status', 'active');
    }

    public function activeCustomerAssignments(): HasMany
    {
        return $this->activeAssignments()->whereHas('user', fn (Builder $query) => $query->customers());
    }

    public function signupSlugAliases(): HasMany
    {
        return $this->hasMany(SignupSlugAlias::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sp(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sp_id');
    }

    public function isPlatformOwned(): bool
    {
        return $this->sp_id === null;
    }

    /** Count of customers currently on this plan. */
    public function activeAssigneesCount(): int
    {
        return $this->activeAssignments()->count();
    }

    /** Whether this plan can be hard-deleted (no active assignments). */
    public function canBeDeleted(): bool
    {
        return $this->activeAssignments()->doesntExist();
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', TariffPlanStatus::Active->value);
    }

    #[Scope]
    protected function assignable(Builder $query): Builder
    {
        return $query->where('status', TariffPlanStatus::Active->value);
    }
}
