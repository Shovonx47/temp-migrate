<?php

declare(strict_types=1);

namespace App\Models;

use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'scope',
        'name',
        'description',
        'permissions',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'sp_role_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if this role includes a specific permission.
     */
    public function hasPermission(string|BackedEnum $permission): bool
    {
        $permValue = $permission instanceof BackedEnum ? $permission->value : $permission;

        return in_array($permValue, $this->permissions ?? [], true);
    }

    /**
     * Scope query to internal Superman roles.
     */
    public function scopeSuperman(Builder $query): Builder
    {
        return $query->where('scope', 'superman');
    }

    /**
     * Scope query to a specific S P reseller.
     */
    public function scopeForSp(Builder $query, int|string $spId): Builder
    {
        return $query->where('scope', (string) $spId);
    }
}
