<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\SendOtpRequest;
use App\Http\Requests\Api\V1\Customer\VerifyOtpRequest;
use App\Services\Auth\OtpVerificationService;
use Illuminate\Http\JsonResponse;

class OtpController extends Controller
{
    public function send(SendOtpRequest $request, OtpVerificationService $otpService): JsonResponse
    {
        $validated = $request->validated();

        $result = $validated['channel'] === 'email'
            ? $otpService->sendEmailOtp($validated['email'])
            : $otpService->sendSmsOtp($validated['phone']);

        return new JsonResponse($result, $result['success'] ? 200 : 422);
    }

    public function verify(VerifyOtpRequest $request, OtpVerificationService $otpService): JsonResponse
    {
        $validated = $request->validated();
        $identifier = $validated['channel'] === 'email' ? $validated['email'] : $validated['phone'];
        $type = $validated['channel'] === 'email' ? 'email' : 'sms';

        $result = $otpService->verifyOtp($identifier, $type, $validated['otp']);

        return new JsonResponse($result, $result['success'] ? 200 : 422);
    }
}
