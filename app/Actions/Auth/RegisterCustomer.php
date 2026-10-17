<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\KycStatus;
use App\Enums\TariffPlanAssignmentStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\TariffPlanAssignment;
use App\Models\User;
use App\Services\Auth\OtpVerificationService;
use App\Services\TariffSlugService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RegisterCustomer
{
    public function __construct(
        private OtpVerificationService $otpService,
        private StoreKycMedia $kycMedia,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?string $signupPlanSlug = null): User
    {
        $signupPlan = null;

        if (filled($signupPlanSlug)) {
            $signupPlan = TariffSlugService::resolve($signupPlanSlug)['plan'] ?? null;

            if ($signupPlan === null) {
                throw ValidationException::withMessages([
                    'plan' => 'This signup link is no longer active.',
                ]);
            }
        }

        if (! $this->otpService->isVerified($data['email'], 'email', $data['email_verification_token'] ?? null)) {
            throw ValidationException::withMessages([
                'email' => 'Please verify your email address using the Send OTP button before creating an account.',
            ]);
        }

        if (! $this->otpService->isVerified($data['phone'], 'sms', $data['phone_verification_token'] ?? null)) {
            throw ValidationException::withMessages([
                'phone' => 'Please verify your mobile number using the Send OTP button before creating an account.',
            ]);
        }

        $normalizedPhone = $this->otpService->normalizePhoneNumber($data['phone']);
        $normalizedEmail = strtolower(trim($data['email']));
        $normalizedNid = trim($data['nid_number'] ?? '');

        $this->assertNoActiveDuplicates($normalizedEmail, $normalizedPhone, $normalizedNid);
        $this->purgeTrashedDuplicates($normalizedEmail, $normalizedPhone, $normalizedNid);

        return DB::transaction(function () use ($data, $normalizedPhone, $signupPlan): User {
            $frontNid = $this->kycMedia->storeFile($data['front_nid'], 'ids');
            $backNid = $this->kycMedia->storeFile($data['back_nid'], 'ids');
            $tradeLicense = $this->kycMedia->storeFile($data['trade_license_file'], 'trade_licenses');
            $livePhoto = $this->kycMedia->storeLivePhoto($data['live_photo'] ?? null);

            if ($livePhoto === null) {
                throw ValidationException::withMessages([
                    'live_photo' => 'A live selfie photo is required for identity verification.',
                ]);
            }

            $payload = [
                'name' => $data['name'],
                'email' => strtolower(trim($data['email'])),
                'phone' => $normalizedPhone ?: $data['phone'],
                'password' => $data['password'],
                'nid_number' => trim($data['nid_number']),
                'front_nid' => $frontNid,
                'back_nid' => $backNid,
                'live_photo' => $livePhoto,
                'business_name' => $data['business_name'],
                'trade_license_number' => $data['trade_license_number'],
                'business_address' => $data['business_address'],
                'trade_license_file' => $tradeLicense,
                'type' => UserType::Customer,
                'status' => UserStatus::Pending,
                'kyc_status' => KycStatus::UnderReview,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'kyc_submitted_at' => now(),
                'trade_license_submitted' => true,
            ];

            if ($signupPlan?->sp_id) {
                $payload['sp_id'] = $signupPlan->sp_id;
            }

            $user = User::withoutGlobalScopes()->create($payload);

            if ($signupPlan) {
                TariffPlanAssignment::create([
                    'user_id' => $user->id,
                    'tariff_plan_id' => $signupPlan->id,
                    'assigned_by' => null,
                    'effective_from' => now()->toDateString(),
                    'status' => TariffPlanAssignmentStatus::Active->value,
                    'notes' => 'Auto-assigned via signup link: /register/'.$signupPlan->slug,
                ]);
            }

            return $user;
        });
    }

    private function assertNoActiveDuplicates(string $email, ?string $phone, string $nid): void
    {
        if (User::withoutGlobalScopes()->whereNull('deleted_at')->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This email address is already registered.',
            ]);
        }

        if ($phone && User::withoutGlobalScopes()->whereNull('deleted_at')->where('phone', $phone)->exists()) {
            throw ValidationException::withMessages([
                'phone' => 'This mobile number is already registered.',
            ]);
        }

        if ($nid !== '' && User::withoutGlobalScopes()->whereNull('deleted_at')->where('nid_number', $nid)->exists()) {
            throw ValidationException::withMessages([
                'nid_number' => 'This NID Number is already registered.',
            ]);
        }
    }

    private function purgeTrashedDuplicates(string $email, ?string $phone, string $nid): void
    {
        User::withoutGlobalScopes()->onlyTrashed()
            ->where(function ($query) use ($email, $phone, $nid): void {
                $query->where('email', $email);
                if ($phone) {
                    $query->orWhere('phone', $phone);
                }
                if ($nid !== '') {
                    $query->orWhere('nid_number', $nid);
                }
            })
            ->each(function (User $trashedUser): void {
                $trashedUser->forceDelete();
            });
    }
}
