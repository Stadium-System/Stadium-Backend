<?php

namespace App\Http\Requests\User\Auth;

use Illuminate\Foundation\Http\FormRequest;

class PasswordResetRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'phone_number' => 'required|string|size:12|regex:/^2189/',
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
            'password' => [
                'description' => 'The new password. Must be at least 8 characters.',
                'example' => 'newpassword123',
                'type' => 'string',
            ],
        ];
    }
}