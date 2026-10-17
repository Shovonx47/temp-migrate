<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyOtpRequest extends FormRequest
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
            'channel' => ['required', Rule::in(['email', 'sms'])],
            'email' => ['required_if:channel,email', 'nullable', 'email'],
            'phone' => ['required_if:channel,sms', 'nullable', 'string', 'max:30'],
            'otp' => ['required', 'string', 'size:6'],
        ];
    }
}
