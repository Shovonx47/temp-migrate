<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Actions\Auth\StoreKycMedia;
use App\Enums\KycStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\SubmitKycRequest;
use App\Http\Resources\CustomerUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KycController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $isResubmission = $user->kyc_status === KycStatus::ResubmitRequired;
        $requested = $isResubmission ? ($user->kyc_resubmit_fields ?? []) : null;

        return new JsonResponse([
            'data' => [
                'user' => CustomerUserResource::make($user)->resolve($request),
                'is_resubmission' => $isResubmission,
                'requested_fields' => $requested,
                'title' => $isResubmission ? 'KYC Resubmission Required' : 'KYC Verification Required',
                'heading' => $isResubmission
                    ? 'Please update and resubmit your KYC documents'
                    : 'Your account requires KYC verification',
                'subheading' => $isResubmission && filled($user->kyc_rejected_reason)
                    ? 'Admin Note: '.$user->kyc_rejected_reason
                    : 'We require KYC verification to ensure the security of our platform. Please submit the necessary documents to complete your account verification.',
            ],
        ]);
    }

    public function submit(SubmitKycRequest $request, StoreKycMedia $kycMedia): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $updatePayload = [
            'kyc_status' => KycStatus::UnderReview,
            'kyc_submitted_at' => now(),
            'kyc_rejected_reason' => null,
            'kyc_resubmit_fields' => null,
        ];

        if (filled($data['nid_number'] ?? null)) {
            $updatePayload['nid_number'] = $data['nid_number'];
        }

        if ($request->hasFile('front_nid')) {
            $updatePayload['front_nid'] = $kycMedia->storeFile($request->file('front_nid'), 'ids');
        }

        if ($request->hasFile('back_nid')) {
            $updatePayload['back_nid'] = $kycMedia->storeFile($request->file('back_nid'), 'ids');
        }

        $livePhoto = $kycMedia->storeLivePhoto($data['live_photo'] ?? null);
        if ($livePhoto !== null) {
            $updatePayload['live_photo'] = $livePhoto;
        }

        if (array_key_exists('trade_license_number', $data)) {
            $updatePayload['trade_license_number'] = $data['trade_license_number'];
        }

        if ($request->hasFile('trade_license_file')) {
            $updatePayload['trade_license_file'] = $kycMedia->storeFile($request->file('trade_license_file'), 'trade_licenses');
            $updatePayload['trade_license_submitted'] = true;
        }

        $user->update($updatePayload);

        return new JsonResponse([
            'message' => 'KYC submitted successfully. Our team will review your documents shortly.',
            'data' => CustomerUserResource::make($user->fresh())->resolve($request),
        ]);
    }
}
