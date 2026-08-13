<?php

namespace App\Models;

use App\Enums\TariffPlanAssignmentStatus;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class TariffPlanAssignment extends Model
{
    protected function casts(): array
    {
        return [
            'status' => TariffPlanAssignmentStatus::class,
            'effective_from' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tariffPlan(): BelongsTo
    {
        return $this->belongsTo(TariffPlan::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', TariffPlanAssignmentStatus::Active->value);
    }
}
