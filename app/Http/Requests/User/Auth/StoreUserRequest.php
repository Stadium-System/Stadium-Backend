<?php

namespace App\Http\Requests\User\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'unique:users,phone_number', 'regex:/^2189\d{8}$/'],
            'password' => ['required', 'string', 'min:8'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'cover' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ];

        return $rules;
    }

    /**
     * Define the body parameters for Scribe documentation.
     *
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'The user\'s full name.',
                'example' => 'John Doe',
                'type' => 'string',
            ],
            'phone_number' => [
                'description' => 'The user\'s phone number. Must start with 2189 and be exactly 12 digits.',
                'example' => '218912345678',
                'type' => 'string',
            ],
            'email' => [
                'description' => 'The user\'s email address.',
                'example' => 'johndoe@example.com',
                'type' => 'string',
            ],
            'type' => [
                'description' => 'The user\'s account type.',
                'example' => 'user',
                'type' => 'string',
                'enum' => ['admin', 'user', 'owner'],
            ],
            'password' => [
                'description' => 'The user\'s password. Must be at least 8 characters.',
                'example' => 'password123',
                'type' => 'string',
            ],
        ];
    }
}
