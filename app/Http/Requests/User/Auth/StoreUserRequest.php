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
            'phone_number' => ['required', 'string', 'regex:/^2189\d{8}$/'],
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
            'avatar_media_id' => [
                'description' => 'The media ID of the user\'s avatar image (obtained from uploading to /api/v1/general/upload/image).',
                'example' => 1,
                'type' => 'integer',
            ],
            'cover_media_id' => [
                'description' => 'The media ID of the user\'s cover image (obtained from uploading to /api/v1/general/upload/image).',
                'example' => 2,
                'type' => 'integer',
            ],
            
        ];
    }
}
