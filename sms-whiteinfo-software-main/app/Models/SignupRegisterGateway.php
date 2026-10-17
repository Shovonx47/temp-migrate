<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SignupRegisterGatewayFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SignupRegisterGateway extends Model
{
    /** @use HasFactory<SignupRegisterGatewayFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'endpoint_url',
        'http_method',
        'bearer_token',
        'sender_id',
        'body_template',
        'phone_format',
        'timeout_seconds',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'timeout_seconds' => 'integer',
            'priority' => 'integer',
            'is_active' => 'boolean',
            'bearer_token' => 'encrypted',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('priority', 'asc')->orderBy('id', 'asc');
    }
}
