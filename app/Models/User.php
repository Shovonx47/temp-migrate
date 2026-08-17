<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Scopes\ScopedBySp;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;

#[Fillable(['name', 'email', 'phone', 'type', 'sp_id', 'status', 'api_access', 'sms_auto_approved', 'balance', 'password', 'front_nid', 'back_nid'])]
#[Hidden(['type', 'password', 'remember_token', 'front_nid', 'back_nid'])]
#[ScopedBy(ScopedBySp::class)]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens,
        HasFactory,
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
            'phone' => E164PhoneNumberCast::class,

            'balance' => 'float',

            'type' => UserType::class,
            'status' => UserStatus::class,
            'api_access' => 'boolean',
            'sms_auto_approved' => 'boolean',

            'password' => 'hashed',
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

    public function tariffPlanAssignments(): HasMany
    {
        return $this->hasMany(TariffPlanAssignment::class);
    }

    /**
     * The currently active tariff plan assignment for this user.
     * Use this to resolve billing rates through the tariff plan system.
     */
    public function activeTariffAssignment(): HasOne
    {
        return $this->hasOne(TariffPlanAssignment::class)
            ->where('status', 'active')
            ->latest('effective_from');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'superman' => $this->type === UserType::Superman,
            'customer' => $this->type === UserType::Customer,
            'sp' => $this->type === UserType::Sp,
            default => false,
        };
    }

    public function sp(): BelongsTo
    {
        return $this->belongsTo(self::class, 'sp_id');
    }

    public function spCustomers(): HasMany
    {
        return $this->hasMany(self::class, 'sp_id')->where('type', UserType::Customer);
    }

    public function spDistributions(): HasMany
    {
        return $this->hasMany(SpDistribution::class, 'sp_id');
    }

    public function receivedDistributions(): HasMany
    {
        return $this->hasMany(SpDistribution::class, 'customer_id');
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

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class, 'user_id');
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
    protected function sps(Builder $query): Builder
    {
        return $query->where('type', UserType::Sp);
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
