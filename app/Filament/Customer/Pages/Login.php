<?php

declare(strict_types=1);

namespace App\Filament\Customer\Pages;

use App\Enums\KycStatus;
use App\Enums\UserStatus;
use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class Login extends BaseLogin
{
    /**
     * Filament's default authenticate() reports the exact same generic
     * "credentials do not match" message for two very different failures:
     *   1. an actually wrong email/password, and
     *   2. a correct email/password whose account simply isn't approved yet.
     *
     * Case (2) is misleading to the customer and is what this override
     * fixes. We check for it BEFORE calling into Filament's own attempt,
     * and only intercept when the submitted password genuinely matches
     * the account on file — so a mistyped password still falls straight
     * through to the normal flow and normal generic message.
     *
     * If the account is fully approved, or the credentials don't match
     * anything, we never intervene: parent::authenticate() runs exactly
     * as it does today, including rate limiting, the canAccessPanel()
     * gate, and session regeneration.
     */
    protected function getRememberFormComponent(): \Filament\Schemas\Components\Component
    {
        return parent::getRememberFormComponent()->default(true);
    }

    public function authenticate(): ?LoginResponse
    {
        $this->data['remember'] = true;

        $data = $this->form->getState();

        if ($message = $this->resolveAccountStatusMessage($data)) {
            Notification::make()
                ->title('Unable to Sign In')
                ->body($message)
                ->warning()
                ->persistent()
                ->send();

            throw ValidationException::withMessages([
                'data.email' => $message,
            ]);
        }

        return parent::authenticate();
    }

    /**
     * Returns a human-readable reason the account can't sign in yet.
     * Returns null (no interference) unless the submitted password truly
     * matches the stored hash for that email — this never leaks account
     * status to someone who simply typed the wrong password.
     *
     * IMPORTANT — the "not yet approved" branch requires status === Pending
     * *AND* kyc_status in [Pending, UnderReview], as a combined condition,
     * not independent checks. This is deliberate:
     *
     *   - An Approved customer whose kyc_status later reads UnderReview
     *     must still be allowed to log in — HandleCustomerKyc explicitly
     *     lets Approved/UnderReview customers reach the dashboard normally.
     *   - A customer with kyc_status = ResubmitRequired always has
     *     status = Pending too (see RequestKycResubmissionAction), but
     *     must NOT be blocked here — HandleCustomerKyc is responsible for
     *     routing them to the KYC resubmission page post-login. Blocking
     *     ResubmitRequired here would trap them with no way to resubmit.
     */
    private function resolveAccountStatusMessage(array $data): ?string
    {
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (blank($email) || blank($password)) {
            return null;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user || ! $user->password || ! Hash::check($password, $user->password)) {
            return null;
        }

        $notYetReviewed = $user->status === UserStatus::Pending
            && in_array($user->kyc_status, [KycStatus::Pending, KycStatus::UnderReview], true);

        return match (true) {
            $user->status === UserStatus::Banned => 'Your account has been banned due to a violation of our Terms & Conditions. Please contact the administrator for assistance.',
            $user->status === UserStatus::Rejected => 'Your account application was rejected. Please contact the administrator for more information.',
            $notYetReviewed => 'Your account status is under review by the admin. Please contact the administrator for further assistance.',
            default => null,
        };
    }
}