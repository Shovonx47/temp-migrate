<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpWhitelistAuditLog extends Model
{
    protected $fillable = [
        'ip_whitelist_request_id',
        'user_id',
        'actor_id',
        'actor_name',
        'actor_type',
        'action',
        'ip_address',
        'old_values',
        'new_values',
        'note',
        'source_ip',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * The customer whose whitelist this affects.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The admin or customer who initiated the change.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * The IP whitelist request (if still present).
     */
    public function ipWhitelistRequest(): BelongsTo
    {
        return $this->belongsTo(IpWhitelistRequest::class, 'ip_whitelist_request_id');
    }

    // ─── Convenience Logger ───────────────────────────────────────────────────

    /**
     * Helper to write an audit entry from anywhere in the application.
     */
    public static function record(
        int $userId,
        string $action,
        string $ipAddress,
        ?int $requestId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $note = null,
        ?User $actor = null,
    ): self {
        $actor = $actor ?? auth()->user();
        $actorType = 'system';

        if ($actor) {
            $actorType = $actor->type?->isSuperman() ? 'admin' : 'customer';
        }

        return self::create([
            'ip_whitelist_request_id' => $requestId,
            'user_id'                 => $userId,
            'actor_id'                => $actor?->id,
            'actor_name'              => $actor?->name ?? 'System',
            'actor_type'              => $actorType,
            'action'                  => $action,
            'ip_address'              => $ipAddress,
            'old_values'              => $oldValues,
            'new_values'              => $newValues,
            'note'                    => $note,
            'source_ip'               => request()->ip(),
        ]);
    }
}
