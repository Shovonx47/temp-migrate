<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Customer;

use Illuminate\Foundation\Http\FormRequest;

class SubmitKycRequest extends FormRequest
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
            'nid_number' => ['nullable', 'string', 'max:30'],
            'front_nid' => ['nullable', 'file', 'image', 'max:4096'],
            'back_nid' => ['nullable', 'file', 'image', 'max:4096'],
            'live_photo' => ['nullable', 'string'],
            'trade_license_number' => ['nullable', 'string', 'max:100'],
            'trade_license_file' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ];
    }
}
