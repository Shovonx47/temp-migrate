<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Actions\Auth\RegisterCustomer;
use App\Enums\KycStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Customer\LoginRequest;
use App\Http\Requests\Api\V1\Customer\RegisterCustomerRequest;
use App\Http\Requests\Api\V1\Customer\ResetPasswordRequest;
use App\Http\Resources\CustomerUserResource;
use App\Models\User;
use App\Services\TariffSlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function plan(string $slug): JsonResponse
    {
        $result = TariffSlugService::resolve($slug);

        if ($result === null) {
            return new JsonResponse([
                'message' => 'This signup link is no longer active.',
            ], 410);
        }

        return new JsonResponse([
            'data' => [
                'slug' => $result['canonical_slug'],
                'name' => $result['plan']->name,
                'is_alias' => $result['is_alias'],
            ],
        ]);
    }

    public function register(RegisterCustomerRequest $request, RegisterCustomer $registerCustomer): JsonResponse
    {
        $user = $registerCustomer->handle(
            $request->validated(),
            $request->validated('plan_slug'),
        );

        Auth::guard('customer')->login($user);
        $request->session()->regenerate();
        $request->session()->forget(TariffSlugService::SESSION_KEY);

        return new JsonResponse([
            'message' => 'Account created. Your KYC is under review.',
            'data' => CustomerUserResource::make($user)->resolve($request),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if ($message = $this->resolveAccountStatusMessage($validated['email'], $validated['password'])) {
            throw ValidationException::withMessages([
                'email' => $message,
            ]);
        }

        $remember = (bool) ($validated['remember'] ?? true);

        if (! Auth::guard('customer')->attempt([
            'email' => $validated['email'],
            'password' => $validated['password'],
        ], $remember)) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        /** @var User $user */
        $user = Auth::guard('customer')->user();

        if ($user->type !== UserType::Customer) {
            Auth::guard('customer')->logout();

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        return new JsonResponse([
            'data' => CustomerUserResource::make($user)->resolve($request),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return new JsonResponse(status: 204);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->safe()->only('email'));

        return new JsonResponse([
            'message' => 'If that email exists in our system, a password reset link has been sent.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->safe()->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                ])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return new JsonResponse([
            'message' => 'Your password has been reset. You can now sign in.',
        ]);
    }

    private function resolveAccountStatusMessage(string $email, string $password): ?string
    {
        $user = User::withoutGlobalScopes()->where('email', $email)->first();

        if (! $user || ! $user->password || ! Hash::check($password, $user->password)) {
            return null;
        }

        $notYetReviewed = $user->status === UserStatus::Pending
            && in_array($user->kyc_status, [KycStatus::Pending, KycStatus::UnderReview], true);

        if ($user->isCreatedByAdmin()) {
            $notYetReviewed = false;
        }

        return match (true) {
            $user->status === UserStatus::Banned => 'Your account has been banned due to a violation of our Terms & Conditions. Please contact the administrator for assistance.',
            $user->status === UserStatus::Rejected => 'Your account application was rejected. Please contact the administrator for more information.',
            $notYetReviewed => 'Your account status is under review by the admin. Please contact the administrator for further assistance.',
            default => null,
        };
    }
}
