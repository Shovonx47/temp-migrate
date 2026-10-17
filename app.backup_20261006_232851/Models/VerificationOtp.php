<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationOtp extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier',
        'type',
        'otp_hash',
        'token',
        'attempts',
        'last_sent_at',
        'expires_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'last_sent_at' => 'datetime',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at === null || $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
