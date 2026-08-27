<?php

namespace App\Models;

use App\Enums\IpWhitelistStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpWhitelistRequest extends Model
{
    protected $fillable = [
        'user_id',
        'ip_address',
        'label',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status'      => IpWhitelistStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', IpWhitelistStatus::Approved);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', IpWhitelistStatus::Pending);
    }
}
