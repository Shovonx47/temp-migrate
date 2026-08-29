<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $session_token
 * @property string|null $photo_data
 * @property string $status
 * @property Carbon $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class PhonePhotoSession extends Model
{
    use HasFactory;
    use HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_EXPIRED = 'expired';

    public const SESSION_TTL_MINUTES = 15;

    protected $table = 'phone_photo_sessions';

    protected $fillable = [
        'session_token',
        'photo_data',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public static function createNew(): self
    {
        return self::create([
            'session_token' => Str::random(64),
            'status' => self::STATUS_PENDING,
            'expires_at' => now()->addMinutes(self::SESSION_TTL_MINUTES),
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || $this->expires_at->isPast();
    }

    public function markCompleted(string $photoData): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'photo_data' => $photoData,
        ]);
    }

    public static function findByTokenOrFail(string $token): self
    {
        return self::where('session_token', $token)->firstOrFail();
    }

    public function scopeNotExpired($query)
    {
        return $query->where('status', '!=', self::STATUS_EXPIRED)
            ->where('expires_at', '>', now());
    }
}
