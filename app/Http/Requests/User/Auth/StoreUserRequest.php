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
            'phone_number' => ['required', 'string', 'regex:/^2189\d{8}$/', Rule::unique('users', 'phone_number')],
            'password' => ['required', 'string', 'min:8'],
            'type' => ['required', 'string', Rule::in(['user', 'owner'])],
            'avatar_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'cover_media_id' => ['nullable', 'integer', 'exists:media,id'],
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
            'password' => [
                'description' => 'The user\'s password. Must be at least 8 characters.',
                'example' => 'securepassword123',
                'type' => 'string',
            ],
            'type' => [
                'description' => 'The type of user account.',
                'example' => 'user',
                'type' => 'string',
                'enum' => ['user', 'owner'],
            ],
            'avatar_media_id' => [
                'description' => 'The media ID of the user\'s avatar image.',
                'example' => 1,
                'type' => 'integer',
            ],
            'cover_media_id' => [
                'description' => 'The media ID of the user\'s cover image.',
                'example' => 2,
                'type' => 'integer',
            ],
        ];
    }
}
