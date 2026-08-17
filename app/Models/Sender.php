<?php

namespace App\Models;

use App\Enums\SenderApprovalStatus;
use App\Enums\SenderType;
use Database\Factories\SenderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'gateway_id',
    'sender_id',
    'type',
    'is_enabled',
    'approval_status',
    'requested_by',
    'reviewed_by',
    'reviewed_at',
    'rejection_reason',
])]
class Sender extends Model
{
    /** @use HasFactory<SenderFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => SenderType::class,
            'approval_status' => SenderApprovalStatus::class,
            'is_enabled' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(Gateway::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sender_user');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'sender_id');
    }

    public function isUsable(): bool
    {
        return $this->is_enabled
            && $this->approval_status === SenderApprovalStatus::Approved;
    }

    #[Scope]
    protected function enabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    #[Scope]
    protected function approved(Builder $query): Builder
    {
        return $query->where('approval_status', SenderApprovalStatus::Approved->value);
    }

    #[Scope]
    protected function pendingApproval(Builder $query): Builder
    {
        return $query->where('approval_status', SenderApprovalStatus::Pending->value);
    }

    #[Scope]
    protected function accessible(Builder $query): Builder
    {
        return $query->where('is_enabled', true)
            ->where('approval_status', SenderApprovalStatus::Approved->value)
            ->whereHas('users', function ($query) {
                $query->where('user_id', auth()->id());
            });
    }
}
