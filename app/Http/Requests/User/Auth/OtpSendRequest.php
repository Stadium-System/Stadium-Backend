<?php

namespace App\Http\Requests\User\Auth;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class OtpSendRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'phone_number' => [
                'required',
                'string',
                'size:12',
                'regex:/^2189/',
                Rule::when(
                    $this->purpose === 'registration',
                    Rule::unique('users'), // Must NOT exist for registration
                    Rule::exists('users')  // Must exist for password reset
                )
            ],
            'purpose' => 'required|in:registration,password_reset'
        ];
    }

    public function messages()
    {
        return [
            'phone_number.unique' => 'This phone number is already registered.',
            'phone_number.exists' => 'This phone number is not registered.',
            'purpose.in' => 'Invalid purpose specified.'
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
            'purpose' => [
                'description' => 'The purpose of the OTP request.',
                'example' => 'registration',
                'type' => 'string',
                'enum' => ['registration', 'password_reset'],
            ],
        ];
    }
}