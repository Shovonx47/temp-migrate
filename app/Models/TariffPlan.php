<?php

namespace App\Models;

use App\Enums\TariffPlanStatus;
use App\Enums\TariffPlanType;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Guarded(['id'])]
class TariffPlan extends Model
{
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
