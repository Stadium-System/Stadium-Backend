<?php

namespace App\Http\Requests\User\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class OtpVerifyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'phone_number' => 'required|string|size:12|regex:/^2189/',
            'otp_code' => 'required|string|size:6',
            'password' => 'required|string|min:8',
            ];
    }

    public function bodyParameters(): array
    {
        return [
            'phone_number' => [
                'description' => 'The user\'s phone number. Must start with 2189 and be exactly 12 digits.',
                'example' => '218912345678',
                'type' => 'string',
            ],
            'otp_code' => [
                'description' => 'The OTP code received by the user. Must be 6 digits.',
                'example' => '123456',
                'type   ' => 'string',
            ],
            'password' => [
                'description' => 'The new password. Must be at least 8 characters.',
                'example' => 'newpassword123',
                'type' => 'string',
            ],
        ];
    }
}