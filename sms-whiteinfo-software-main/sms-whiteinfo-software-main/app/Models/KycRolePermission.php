<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycRolePermission extends Model
{
    protected $fillable = [
        'user_id',
        'permissions',
        'granted_by',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * Check whether a specific permission key is in the granted set.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }
}
