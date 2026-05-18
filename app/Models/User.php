<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserApproval;
use App\Enums\UserType;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;

#[Fillable(['name', 'email', 'phone', 'type', 'approval', 'api_access', 'sms_auto_approved', 'balance', 'password', 'front_nid', 'back_nid'])]
#[Hidden(['type', 'password', 'remember_token', 'front_nid', 'back_nid'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication
{
    /** @use HasFactory<UserFactory> */
    use HasFactory,
        InteractsWithAppAuthentication,
        InteractsWithAppAuthenticationRecovery,
        Notifiable,
        SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => UserType::class,
            'approval' => UserApproval::class,
            'api_access' => 'boolean',
            'sms_auto_approved' => 'boolean',
            'phone' => E164PhoneNumberCast::class,
            'password' => 'hashed',
            'balance' => 'float',
            'email_verified_at' => 'datetime',
        ];
    }

    public function requiresSmsApproval(): bool
    {
        return ! $this->sms_auto_approved;
    }

    public function operatorRates(): HasMany
    {
        return $this->hasMany(UserOperatorRate::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'superman' => $this->type === UserType::Superman,
            'customer' => $this->type === UserType::Customer,
        };
    }

    public function senders(): BelongsToMany
    {
        return $this->belongsToMany(Sender::class, 'sender_user');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class, 'user_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'user_id');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class, 'user_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'user_id');
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class, 'user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    #[Scope]
    protected function customers(Builder $query): Builder
    {
        return $query->where('type', UserType::Customer);
    }

    #[Scope]
    protected function emailVerified(Builder $query): Builder
    {
        return $query->whereNotNull('email_verified_at');
    }

    #[Scope]
    protected function phoneVerified(Builder $query): Builder
    {
        return $query->whereNotNull('phone_verified_at');
    }

    public function canImpersonate(): bool
    {
        return $this->type->isSuperman();
    }

    public function canBeImpersonated(): bool
    {
        return $this->type->isCustomer();
    }
}
