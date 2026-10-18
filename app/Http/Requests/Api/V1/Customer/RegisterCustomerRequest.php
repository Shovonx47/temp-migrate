<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::default()],
            'email_verification_token' => ['required', 'string'],
            'phone_verification_token' => ['required', 'string'],
            'nid_number' => ['required', 'string', 'max:30'],
            'front_nid' => ['required', 'file', 'image', 'max:4096'],
            'back_nid' => ['required', 'file', 'image', 'max:4096'],
            'live_photo' => ['required', 'string'],
            'business_name' => ['required', 'string', 'max:255'],
            'trade_license_number' => ['required', 'string', 'max:100'],
            'business_address' => ['required', 'string', 'max:500'],
            'trade_license_file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
            'terms' => ['accepted'],
            'plan_slug' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'terms.accepted' => 'You must read and agree to the Terms & Conditions and Privacy Policy before creating your account.',
            'live_photo.required' => 'A live selfie photo is required for identity verification.',
        ];
    }
}
